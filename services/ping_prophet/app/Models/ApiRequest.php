<?php

namespace app\Models;

use app\Data\ApiRequestMetaData;
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

    public function setMetaData(ApiRequestMetaData $metaData, bool $save = true): self
    {
        $meta = $this->meta ?? [];
        $this->meta = array_merge($meta, $metaData->toArray());

        if ($save) {
            $this->save();
        }

        return $this;
    }
}
