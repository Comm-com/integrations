<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class ApiRequestMetaData extends Data
{
    public function __construct(
        public array     $data,
        public int|float $cost,
        public ?string   $callback_url = null,
        public ?string   $completed_at = null,
    )
    {
    }
}
