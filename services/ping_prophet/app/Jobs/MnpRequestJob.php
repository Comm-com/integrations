<?php

namespace app\Jobs;

use app\Data\MnpRequestData;
use app\Services\Providers\XConnectProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MnpRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly MnpRequestData $data,
    )
    {
    }

    public function handle(): void
    {
        app(XConnectProvider::class)->lookup($this->data);
    }
}
