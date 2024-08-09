<?php

namespace app\Data;

use app\Enums\LookupResultStatusEnum;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

class LookupResultData extends Data
{
    public function __construct(
        public LookupResultStatusEnum $status,
        public bool                   $verified,
        #[Uuid]
        public ?string                $contact_id = null,
        public ?int                   $network_id = null,
    )
    {
    }
}
