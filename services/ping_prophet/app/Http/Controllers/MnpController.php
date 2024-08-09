<?php

namespace app\Http\Controllers;

use app\Data\ApiRequestMetaData;
use app\Enums\ApiRequestStatusEnum;
use app\Enums\LookupTypeEnum;
use app\Jobs\MnpDispatchJob;
use app\Models\ApiRequest;
use app\Services\BalanceService;
use app\Services\LookupService;
use Illuminate\Http\Request;

class MnpController extends Controller
{
    public function index()
    {
        return [
            'status' => 'success',
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'data' => 'required|array',
            'data.*.number' => 'required|string',
            'data.*.foreign_id' => 'nullable|string',
            'reference' => 'string',
            'callback_url' => 'nullable|string',
        ]);

//        $balanceService = app(BalanceService::class, ['team_id' => $request->user()->currentTeam->id]);
//        $balance = $balanceService->total();
        $cost = app(LookupService::class)->calculateCost(count($validated['data']), LookupTypeEnum::mnp->name);
//
//        if ($balance < $cost) {
//            return response()->json(['status' => 'error', 'message' => 'Insufficient balance'], 402);
//        }

        $meta = ApiRequestMetaData::from([
            'data' => $validated['data'],
            'cost' => $cost,
            'callback_url' => $validated['callback_url'],
        ]);
        $apiRequest = ApiRequest::create([
            'team_id' => $request->user()->currentTeam->id,
            'request_type' => LookupTypeEnum::mnp->name,
            'status' => ApiRequestStatusEnum::processing->name,
            'meta' => $meta->toArray(),
        ]);

//        $balanceService->subtractBalance(
//            amount: $cost,
//            meta: [
//                'api_request_id' => $apiRequest->id,
//                'request_type' => $apiRequest->request_type,
//                'total_numbers' => count($validated['data']),
//            ],
//        );

        MnpDispatchJob::dispatch($apiRequest, $validated['data']);

        return [
            'data' => [
                'request_id' => $apiRequest->id,
            ],
            'status' => 'success',
        ];
    }
}