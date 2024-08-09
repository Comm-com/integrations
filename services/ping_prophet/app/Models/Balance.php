<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Balance extends Model
{
    use HasUuids;

    protected $fillable = [
        'amount',
        'team_id',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    protected function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
