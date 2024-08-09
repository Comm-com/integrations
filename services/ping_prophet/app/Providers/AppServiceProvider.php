<?php

namespace app\Providers;

use Dedoc\Scramble\Scramble;
use Illuminate\Routing\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Scramble::routes(function (Route $route) {
            if (app()->environment('local')) {
                return Str::startsWith($route->uri, 'api/');
            }

            return Str::startsWith($route->uri, 'api/') && !Str::startsWith($route->uri, 'api/fake');
        });
    }
}
