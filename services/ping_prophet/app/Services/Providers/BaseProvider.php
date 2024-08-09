<?php

namespace app\Services\Providers;

use app\Data\MnpRequestData;

abstract class BaseProvider
{
    public function __construct() {}

    public function lookup(MnpRequestData $requestData): void
    {
        throw new \Exception('Method not implemented');
    }
}
