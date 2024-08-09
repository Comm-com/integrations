<?php

namespace app\Http\Controllers;

use Illuminate\Http\Request;

/**
 * HLR
 */
class HlrController extends Controller
{

    public function index(Request $request)
    {
        $team = auth()->user()->current_team_id;
//        $hlr = \App\Models\Hlr::where('team_id', $team)->get();
        return [
            'status' => 'success',
        ];
    }
    
    public function store(Request $request)
    {
        
    }
}