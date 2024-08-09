<?php

use app\Http\Controllers\IntegrationsController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
    Route::get('/api-reference', function () {
        return Inertia::render('Dashboard');
    })->name('api-reference');
    Route::get('/balance', function () {
        return Inertia::render('Balance',
            ['transactions' => \App\Models\Balance::where('team_id', auth()->user()->current_team_id)->get()]
        );
    })->name('balance');
    Route::get('/oauth', function () {
        return Inertia::render('Auth/Oauth');
    })->name('oauth');
    Route::get('/integrations', function () {
        return Inertia::render('Integrations/Index');
    })->name('integrations.index');
    Route::get('/integrations/{id}', function () {
        return Inertia::render('Integrations/[id]');
    })->name('integrations.view');
});
