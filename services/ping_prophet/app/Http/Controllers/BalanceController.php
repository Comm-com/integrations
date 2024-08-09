<?php

namespace app\Http\Controllers;

use app\Http\Controllers\Controller;
use app\Models\Balance;
use Illuminate\Http\Request;

class BalanceController extends Controller
{

    public function index()
    {
        
    }

    public function total(Request $request)
    {
        return response()->json([
            'data' => [
                'amount' => Balance::where('team_id', auth()->user()->current_team_id)->sum('amount')
            ]
        ]);
    }
}