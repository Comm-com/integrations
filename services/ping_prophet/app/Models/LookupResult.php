<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LookupResult extends Model
{
    use HasUuids;

    protected $fillable = [
        'api_request_id',
        'callback_id',
        'callback_code',
        'status',
        'phone_normalized',
        'foreign_id',
        'provider_price',
        'admin_price',
        'lookup_type',
        'verified',
        'brand_name',
        'mcc',
        'mnc',
        'country_code',
        'reason_code',
        'raw_response',
    ];

    protected $casts = [
        'raw_response' => 'array',
    ];

    public function apiRequest()
    {
        return $this->belongsTo(ApiRequest::class);
    }
}
