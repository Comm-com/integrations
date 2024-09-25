<?php

namespace app\Console\Commands;

use App\Data\MnpRequestData;
use App\Jobs\MnpRequestJob;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MnpTestSingle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mnp:test-single {--number=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test a single MNP lookup';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        MnpRequestJob::dispatchSync(new MnpRequestData(
            number: $this->option('number'),
            api_request_id: Str::uuid()->toString(),
            total_numbers: 1,
            processed_numbers: 0,
        ));
    }
}
