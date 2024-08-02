<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataProviderCollHasTeam extends Model
{
    use HasFactory;

    protected $fillable = [
        'data_provider_coll_id',
        'team_id',
    ];

    /**
     * Table associated with this model
     *
     * @var string
     */
    protected $table = 'data_provider_coll_has_teams';

    /**
     * Indicates if this model is timestamped
     *
     * @var bool
     */
    public $timestamps = false;
}
