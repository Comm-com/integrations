<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//Route::get('/user', function (Request $request) {
//    return $request->user();
//})->middleware('auth:sanctum');

//Route::prefix('integrations')->group(function () {
//    //login and request approval
//    Route::get('oauth', [\App\Http\Controllers\IntegrationsController::class, 'oauth']);
//    
//    //receive activation callback - store tokens
//    Route::post('activate', [\App\Http\Controllers\IntegrationsController::class, 'activate']);
//});

Route::prefix('fake')->group(function () {
    Route::get('xconnect', [\App\Http\Controllers\FakeServiceController::class, 'xConnect'])
        ->name('fake.xconnect');
});

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    //balance, mnp, domains, api key validate
    Route::apiResource('hlr', \App\Http\Controllers\HlrController::class);
    Route::apiResource('mnp', \App\Http\Controllers\MnpController::class)->only(['index', 'store']);

    Route::get('user', [\App\Http\Controllers\UserController::class, 'show']);
    
    Route::prefix('user')->group(function () {
        Route::get('balance', [\App\Http\Controllers\BalanceController::class, 'index']);
        Route::get('balance/total', [\App\Http\Controllers\BalanceController::class, 'total']);
    });

    Route::prefix('integrations')->group(function () {
        Route::post('activate', [\App\Http\Controllers\IntegrationsController::class, 'activate']);
        Route::post('deactivate', [\App\Http\Controllers\IntegrationsController::class, 'deactivate']);
    });
});
