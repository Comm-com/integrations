<?php

namespace App\Services\Providers;

use App\Data\LookupResultData;
use App\Data\MnpRequestData;
use App\Enums\LookupResultStatusEnum;
use App\Enums\LookupTypeEnum;
use App\Enums\XConnectReasonCodeEnum;
use App\Models\LookupResult;
use App\Services\LookupService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class XConnectProvider extends BaseProvider
{
    public function lookup(MnpRequestData $requestData): void
    {
        $url = str_replace('{number}', $requestData->number, config('services.xconnect.lookup_url'));

        Log::debug('XConnectProvider lookup', [
            'url' => $url,
            'requestData' => $requestData,
        ]);

        $response = Http::withHeaders($this->getHeaders())
            ->timeout(10)
            ->throw(function (Response $response, RequestException $e) use ($requestData) {
                $this->storeResult(
                    requestData: $requestData,
                    resultData: new LookupResultData(
                        status: LookupResultStatusEnum::request_error,
                        verified: false,
                    ),
                    response: $response->json() ?? ['error' => $e->getMessage()],
                );
            })
            ->get($url);

        Log::debug('XConnectProvider response', [
            'response' => $response->json() ?? $response->body(),
        ]);

        if (!$response->ok()) {
            $this->storeResult(
                requestData: $requestData,
                resultData: new LookupResultData(
                    status: LookupResultStatusEnum::request_error,
                    verified: false,
                ),
                response: $response->json() ?? ['error' => 'Unknown error'],
            );

            return;
        }

        $res = $response->json() ?? [];
        $resultData = $this->parseResponse($res);
        $this->storeResult(
            requestData: $requestData,
            resultData: $resultData,
            response: $res,
        );
    }

    private function storeResult(
        MnpRequestData $requestData,
        LookupResultData $resultData,
        ?array $response = null,
    ): void {
        LookupResult::create([
//            'check_id' => $requestData->check_id,
            'api_request_id' => $requestData->api_request_id,
//            'callback_id' => $requestData->callback_id,
//            'callback_code' => $requestData->callback_code,
            'status' => $resultData->status->value,
            'phone_normalized' => $requestData->number,
            'foreign_id' => $requestData->foreign_id,
//            'contact_id' => $resultData->contact_id,
            'provider_price' => 0, // todo: change to real price
            'admin_price' => LookupService::COST_MNP_PER_LOOKUP,
            'lookup_type' => LookupTypeEnum::mnp->name,
            'verified' => $resultData->verified,
            'mcc' => $resultData->mcc,
            'mnc' => $resultData->mnc,
            'country_code' => $resultData->country_code,
            'brand_name' => $resultData->carrier_name,
            'reason_code' => $resultData->status->name,
            'raw_response' => $response,
        ]);
    }

    private function parseResponse(array $response): ?LookupResultData
    {
        // response example from docs
        $responseCode = XConnectReasonCodeEnum::tryFrom($response['rc'] ?? null);

        if ($responseCode === null) {
            $status = LookupResultStatusEnum::parse_error;
        } else {
            $status = $responseCode === XConnectReasonCodeEnum::successful
                ? LookupResultStatusEnum::success
                : LookupResultStatusEnum::bad_response_code;
        }

        $nt = mb_strtolower($response['nt'] ?? 'unknown');

        return new LookupResultData(
            status: $status,
            verified: $nt === 'wireless' && !empty($response['mcc']) && !empty($response['mnc']),
            country_code: $response['cc'] ?? null,
            carrier_name: $response['cn'] ?? null,
            mcc: $response['mcc'] ?? null,
            mnc: $response['mnc'] ?? null,
        );
    }

    private function getHeaders(): array
    {
        $basicAuth = config('services.xconnect.basic_auth');
        $bearerToken = config('services.xconnect.bearer_token');

        if (!empty($bearerToken)) {
            return ['Authorization' => 'Bearer ' . config('services.xconnect.bearer_token')];
        }

        if (!empty($basicAuth)) {
            return ['Authorization' => 'Basic ' . $basicAuth];
        }

        throw new \Exception('XConnect No auth method provided');
    }
}
