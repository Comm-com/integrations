<?php

namespace app\Console\Commands;

use app\Services\LookupService;
use Illuminate\Console\Command;

class LookupFinisher extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mnp:lookup-finisher';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark lookups as completed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        app(LookupService::class)->markAsCompleted();
    }
}
