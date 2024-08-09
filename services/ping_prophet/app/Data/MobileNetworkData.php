<?php

namespace app\Data;

use Spatie\LaravelData\Data;

class MobileNetworkData extends Data
{
    public function __construct(
        public ?int    $mcc = null,
        public ?int    $mnc = null,
        public ?string $type = null,
        public ?string $country_name = null,
        public ?string $country_code = null,
        public ?int    $country_id = null,
        public ?string $brand = null,
        public ?string $operator = null,
        public ?string $status = null,
        public ?string $bands = null,
    )
    {
    }
}
