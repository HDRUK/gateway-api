<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Licence extends Model
{
    use HasFactory, Notifiable, SoftDeletes, Prunable;

    protected $table = 'licences';

    public $timestamps = true;

    protected $fillable = [
        'code',
        'label',
        'valid_since',
        'valid_until',
        'definition',
        'deleted_at',
    ];
}
