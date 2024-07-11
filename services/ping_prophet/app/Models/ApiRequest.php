<?php

namespace App\Models;

use App\Data\ApiRequestMetaData;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ApiRequest extends Model
{
    use HasUuids;
    
    protected $fillable = [
        'team_id',
        'request_type',
        'status',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function metaData(): ApiRequestMetaData
    {
        return new ApiRequestMetaData(
            data: $this->meta['numbers'] ?? [],
            cost: $this->meta['cost'] ?? 0,
        );
    }
}
