<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AppRegistration extends Model
{
    use HasFactory, Notifiable, SoftDeletes, Prunable;

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'app_registrations';

    protected $fillable = [
        'name',
        'app_id',
        'client_id',
        'logo',
        'description',
        'team_id',
        'user_id',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];
}
