<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class License extends Model
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use Prunable;

    protected $table = 'licenses';

    public $timestamps = true;

    protected $fillable = [
        'code',
        'label',
        'valid_since',
        'valid_until',
        'definition',
        'deleted_at',
        'verified',
        'origin',
    ];
}
