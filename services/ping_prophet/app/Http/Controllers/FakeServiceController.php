<?php

namespace App\Http\Controllers;

use App\Enums\XConnectReasonCodeEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

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
                'cc' => null,
                'cn' => null,
                'nt' => 'wireless',
            ]);
        }

        $networks = File::get(storage_path('mobile_networks.json'));
        $network = collect(json_decode($networks, true))->random();

        return [
            'rc' => XConnectReasonCodeEnum::successful->value,
            'mcc' => $network['mcc'],
            'mnc' => $network['mnc'],
            'cc' => $network['iso'],
            'cn' => $network['brand'],
            'nt' => 'wireless',
        ];
    }
}
