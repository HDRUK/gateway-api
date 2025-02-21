<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamHasDataAccessApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id', 'dar_application_id',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'team_has_dar_applications';
}
