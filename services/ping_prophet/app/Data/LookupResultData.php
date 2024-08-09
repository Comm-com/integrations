<?php

namespace App\Data;

use App\Enums\LookupResultStatusEnum;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

class LookupResultData extends Data
{
    public function __construct(
        public LookupResultStatusEnum $status,
        public bool                   $verified,
        public ?string                $country_code = null,
        public ?string                $carrier_name = null,
        public string|int|null        $mcc = null,
        public string|int|null        $mnc = null,
        public string|int|null        $reason_code = null,
    )
    {
    }
}
