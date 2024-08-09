<?php

namespace App\Services;

use App\Data\ApiRequestMetaData;
use App\Enums\ApiRequestStatusEnum;
use App\Enums\EventTypeEnum;
use App\Enums\LookupResultStatusEnum;
use App\Enums\LookupTypeEnum;
use App\Models\ApiRequest;
use App\Models\LookupResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LookupService
{
    public const COST_MNP_PER_LOOKUP = 0.001;
    public const COST_HLR_PER_LOOKUP = 0.002;

    public function calculateCost(int $totalNumbers, string $lookupType): float
    {
        return match ($lookupType) {
            LookupTypeEnum::mnp->name => $totalNumbers * self::COST_MNP_PER_LOOKUP,
            LookupTypeEnum::hlr->name => $totalNumbers * self::COST_HLR_PER_LOOKUP,
        };
    }

    public function markAsCompleted(): void
    {
        ApiRequest::where('status', ApiRequestStatusEnum::processing->name)
            ->chunk(50, function ($requests) {
                /** @var ApiRequest[] $requests */
                foreach ($requests as $request) {
                    if ($this->completeRequest($request)) {
//                        $this->refundFails($request);

                        $this->callback($request);
                        $this->bill_user($request);
                    }
                }
            });
    }

    public function completeRequest(ApiRequest $apiRequest): bool
    {
        $results = LookupResult::where('api_request_id', $apiRequest->id)->get()->count();
        $metaData = ApiRequestMetaData::from($apiRequest->meta);

        if ($results === count($metaData->data)) {
            Log::info('Lookup completed', ['api_request_id' => $apiRequest->id]);

            $apiRequest->status = ApiRequestStatusEnum::completed->name;
            $metaData->completed_at = now()->toDateTimeString();
            $apiRequest->meta = $metaData->toArray();
            return $apiRequest->save();
        }

        if ($apiRequest->created_at->diffInDays(now()) > 1) {
            Log::info('Lookup failed', ['api_request_id' => $apiRequest->id]);

            $apiRequest->status = ApiRequestStatusEnum::failed->name;
            return $apiRequest->save();
        }

        return false;
    }

    public function callback(ApiRequest $apiRequest): void
    {
        $metaData = $apiRequest->getMetaData();

        if ($metaData->callback_url === null) {
            return;
        }

        Log::debug('Callback started', [
            'api_request_id' => $apiRequest->id,
            'callback_url' => $metaData->callback_url,
        ]);

        LookupResult::where('api_request_id', $apiRequest->id)
            ->where('status', LookupResultStatusEnum::success->value)
            ->chunkById(1000, function ($results) use ($apiRequest, $metaData) {
                $payload = [
                    'event_type' => EventTypeEnum::mnp_response->value,
                    'request_id' => $apiRequest->id,
                    'data' => $results->map(function (LookupResult $result) {
                        return [
                            'number' => $result->phone_normalized,
                            'foreign_id' => $result->foreign_id,
                            'verified' => (int)$result->verified,
                            'mcc' => $result->mcc,
                            'mnc' => $result->mnc,
                            'brand_name' => $result->brand_name,
                            'country_code' => $result->country_code,
                            'reason_code' => $result->reason_code,
                            'raw_response' => $result->raw_response,
                        ];
                    }),
                ];

                $this->sendPayload($apiRequest, $metaData, $payload);
            });
    }

    public function bill_user(ApiRequest $apiRequest): void
    {
        $metaData = $apiRequest->getMetaData();
        $totalSuccess = LookupResult::where('api_request_id', $apiRequest->id)
            ->where('status', '!=', LookupResultStatusEnum::success->value)
            ->get()
            ->count();

        if ($totalSuccess === 0) {
            return;
        }

        $cost = $this->calculateCost($totalSuccess, $apiRequest->request_type);
        $metaData->cost = $cost;
        $metaData->billed_at = now()->toDateTimeString();
        $apiRequest->setMetaData($metaData);

        $payload = [
            'event_type' => EventTypeEnum::billing_charge->value,
            'request_id' => $apiRequest->id,
            'cost' => $cost,
            'reason' => "MNP lookup for {$totalSuccess} numbers",
        ];

        $this->sendPayload($apiRequest, $metaData, $payload);
    }

    private function sendPayload(ApiRequest $apiRequest, ApiRequestMetaData $meta, array $payload): void
    {
        $response = Http::post($meta->callback_url, $payload);

        Log::debug('Callback response', [
            'api_request_id' => $apiRequest->id,
            'callback_url' => $meta->callback_url,
            'response' => $response->json(),
        ]);

        if ($response->failed()) {
            Log::error('Callback failed', [
                'api_request_id' => $apiRequest->id,
                'callback_url' => $meta->callback_url,
                'response' => $response->json(),
            ]);
        }
    }
}