<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class MnpRequestData extends Data
{
    public function __construct(
        public string $number,
        public string $api_request_id,
        public int    $total_numbers,
        public int    $processed_numbers,
        public ?string $foreign_id = null,
    )
    {
    }

}