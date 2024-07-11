<?php

namespace App\Jobs;

use App\Data\MnpRequestData;
use App\Models\ApiRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MnpDispatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly ApiRequest $apiRequest,
        public readonly array $data,
    )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $i = 0;
        foreach ($this->data as $dataItem) {
            $i++;
            MnpRequestJob::dispatch(MnpRequestData::from([
                'number' => $dataItem['number'],
                'foreign_id' => $dataItem['foreign_id'] ?? null,
                'api_request_id' => $this->apiRequest->id,
                'total_numbers' => count($this->data),
                'processed_numbers' => $i,
            ]));
        }
    }
}
