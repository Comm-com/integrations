<?php

namespace App\Http\Controllers;

use App\Enums\XConnectReasonCodeEnum;
use App\Models\MobileNetwork;
use Illuminate\Http\Request;

class FakeServiceController extends Controller
{
    public function xConnect(Request $request)
    {
        $request->validate([
            'number' => 'required|string',
        ]);

        if (mt_rand(1, 3) === 2) {
            return response()->json([
                'rc' => XConnectReasonCodeEnum::invalid_query_format->value,
                'mcc' => null,
                'mnc' => null,
                'nt' => 'wireless',
            ]);
        }

        $network = MobileNetwork::inRandomOrder()->first();

        return [
            'rc' => XConnectReasonCodeEnum::successful->value,
            'mcc' => $network->mcc,
            'mnc' => $network->mnc,
            'nt' => 'wireless',
        ];
    }
}
