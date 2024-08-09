<?php

namespace app\Services\Providers;

use app\Data\LookupResultData;
use app\Data\MnpRequestData;
use app\Enums\LookupResultStatusEnum;
use app\Enums\LookupTypeEnum;
use app\Enums\XConnectReasonCodeEnum;
use app\Models\LookupResult;
use app\Services\LookupService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class XConnectProvider extends BaseProvider
{
    public function lookup(MnpRequestData $requestData): void
    {
        $url = str_replace('{number}', $requestData->number, config('services.xconnect.lookup_url'));
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
            'network_id' => $resultData->network_id,
            'raw_response' => $response,
        ]);
    }

    private function parseResponse(array $response): ?LookupResultData
    {
        $responseCode = XConnectReasonCodeEnum::tryFrom($response['rc'] ?? null);

        if ($responseCode === null) {
            $status = LookupResultStatusEnum::parse_error;
        } else {
            $status = $responseCode === XConnectReasonCodeEnum::successful
                ? LookupResultStatusEnum::success
                : LookupResultStatusEnum::bad_response_code;
        }

        $verified = false;
        $networkId = null;
        $mcc = $response['mcc'] ?? null;
        $mnc = $response['mnc'] ?? null;
        $nt = mb_strtolower($response['nt'] ?? 'unknown');

        if ($nt === 'wireless' && !empty($mcc) && !empty($mnc)) {
            $verified = true;
            $networkId = $this->networkService->guessNetworkId($mcc, $mnc);

            if (empty($networkId)) {
                $networkId = $this->networkService->createNewNetwork($mcc, $mnc);
            }
        }

        return new LookupResultData(
            status: $status,
            verified: $verified,
            network_id: $networkId,
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
