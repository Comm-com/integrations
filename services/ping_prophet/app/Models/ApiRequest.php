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

    public function getMetaData(): ApiRequestMetaData
    {
        return new ApiRequestMetaData(
            data: $this->meta['data'] ?? [],
            cost: $this->meta['cost'] ?? 0,
            callback_url: $this->meta['callback_url'] ?? null,
            completed_at: $this->meta['completed_at'] ?? null,
            billed_at: $this->meta['billed_at'] ?? null,
        );
    }

    public function setMetaData(ApiRequestMetaData $metaData, bool $save = true): self
    {
        $meta = $this->meta ?? [];
        $meta = array_merge($meta, $metaData->toArray());
        $this->meta = $meta;

        if ($save) {
            $this->save();
        }

        return $this;
    }
}
