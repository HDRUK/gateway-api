<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureFlag extends Model
{
    protected $fillable = ['key', 'enabled'];

    protected $casts = [
        'enabled' => 'boolean',
    ];
}
