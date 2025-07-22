<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetadataVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'dataset_id',
        'version',
        'patch',
        'snapshot',
    ];

    protected $casts = [
        'patch' => 'array',
    ];
}
