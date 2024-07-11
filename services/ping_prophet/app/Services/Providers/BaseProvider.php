<?php

namespace App\Services\Providers;

use App\Data\LookupResultData;
use App\Data\MnpRequestData;
use App\Services\NetworkService;

abstract class BaseProvider
{
    public function __construct(public NetworkService $networkService) {}

    public function lookup(MnpRequestData $requestData): void
    {
        throw new \Exception('Method not implemented');
    }
}
