<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TeamHasUser extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'team_has_users';

    /**
     * Indicates if the model should be timestamped
     * 
     * @var bool
     */
    public $timestamps = false;
    
    protected $fillable = [
        'user_id',
        'team_id',
    ];

    // public function user()
    // {
    //     return $this->belongsTo(User::class);
    // }

    // public function team()
    // {
    //     return $this->belongsTo(Team::class);
    // }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'team_user_has_permissions');
    }
}
