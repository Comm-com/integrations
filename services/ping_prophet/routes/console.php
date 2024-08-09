<?php

use App\Console\Commands\LookupFinisher;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command(LookupFinisher::class)
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
