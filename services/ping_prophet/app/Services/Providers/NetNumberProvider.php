<?php

namespace app\Services\Providers;

use app\Domain\Audience\Enrichments\Data\LookupResultData;
use app\Domain\Audience\Enrichments\Enums\LookupProviderEnum;
use app\Domain\Audience\Enrichments\Enums\LookupResultStatusEnum;
use app\Domain\Audience\Enrichments\Enums\LookupTypeEnum;
use app\Domain\Audience\Enrichments\Enums\NetNumberResponseCodeEnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NetNumberProvider extends BaseProvider
{
    public function sendRequest(): void
    {
        $request = Http::withHeaders([
            'Authorization' => 'Basic ' . config('services.netnumber.token'),
        ])
            ->withBody($this->getRequestBody())
            ->post(config('services.netnumber.lookup_url'));

        if ( ! $request->ok()) {
            Log::error('Lookup request failed', [
                'data' => $this->data,
                'response' => $request->json(),
                'status_code' => $request->status(),
            ]);

            return;
        }

        $this->response = $request->json();
    }

    public function parseResponse(): ?LookupResultData
    {
        $responseCode = NetNumberResponseCodeEnum::tryFrom($this->response['response_code'] ?? null);

        if ($responseCode === null) {
            $status = LookupResultStatusEnum::parse_error;
        } else {
            $status = $responseCode->equals(NetNumberResponseCodeEnum::no_error())
                ? LookupResultStatusEnum::success
                : LookupResultStatusEnum::bad_response_code;
        }

        $phoneIsGood = false;
        $networkId = null;
        $hni = Arr::get($this->response, 'cid.hni');
        $type = mb_strtolower(Arr::get($this->response, 'cid.type', 'unknown'));

        if ($type === 'mobile' && ! empty($hni)) {
            $phoneIsGood = true;
            $networkId = $this->networkService->guessNetworkId(null, null, $hni);

            if (empty($networkId)) {
                $networkId = $this->networkService->createNewNetwork(null, null, $hni);
            }
        }

        return new LookupResultData(
            phone_normalized: $this->data->phone_normalized,
            contact_id: $this->data->contact_id,
            status: $status,
            phone_is_good: $phoneIsGood,
            network_id: $networkId,
        );
    }

    public function getProviderEnum(): LookupProviderEnum
    {
        return LookupProviderEnum::NetNumber;
    }

    public function getTypeEnum(): LookupTypeEnum
    {
        return LookupTypeEnum::MNP;
    }

    private function getRequestBody(): string
    {
        $phoneNormalized = $this->data->phone_normalized;

        if ( ! str_starts_with($phoneNormalized, '+')) {
            $phoneNormalized = '+' . $phoneNormalized;
        }

        return json_encode(['e164' => $phoneNormalized]);
    }
}
