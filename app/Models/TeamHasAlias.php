<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamHasAlias extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'team_has_aliases';

    protected $fillable = [
        'team_id',
        'alias_id',
    ];
}
