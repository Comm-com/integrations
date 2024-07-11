<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MobileNetwork extends Model
{
    protected $fillable = [
        'mcc',
        'mnc',
        'brand',
    ];
}
