<?php

namespace app\Models;

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
        'network_id',
        'raw_response',
    ];

    protected $casts = [
        'raw_response' => 'array',
    ];

    public function apiRequest()
    {
        return $this->belongsTo(ApiRequest::class);
    }

    public function network()
    {
        return $this->belongsTo(MobileNetwork::class);
    }
}
