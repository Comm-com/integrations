<?php

namespace App\Jobs;

use App\Models\ApiRequest;
use App\Services\LookupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CallbackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly ApiRequest $apiRequest,
    )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        app(LookupService::class)->callback($this->apiRequest);
    }
}
