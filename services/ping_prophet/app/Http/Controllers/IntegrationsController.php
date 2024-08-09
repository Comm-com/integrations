<?php

namespace app\Http\Controllers;

use Illuminate\Http\Request;

class IntegrationsController extends Controller
{
    public function activate(Request $request)
    {
        // todo: do something on activation?

        return response()->json(['status' => 'success']);
    }

    public function deactivate(Request $request)
    {
        // todo: revoke the current access token?
//        $request->user()->currentAccessToken()->delete();

        return response()->json(['status' => 'success']);
    }
}
