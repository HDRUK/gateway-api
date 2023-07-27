<?php

namespace App\Models;

use App\Models\Tag;
use App\Models\Team;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Application extends Model
{
    use HasFactory, Notifiable, SoftDeletes, Prunable;

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'applications';

    protected $fillable = [
        'name',
        'app_id',
        'client_id',
        'image_link',
        'description',
        'team_id',
        'user_id',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'application_has_permissions');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'application_has_tags');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeGetAll($query, $jwtUser)
    {
        if (!count($jwtUser)) {
            return $query;
        }

        $userId = $jwtUser['id'];
        $userIsAdmin = (bool) $jwtUser['is_admin'];

        if (!$userIsAdmin) {
            return $query->where('user_id', $userId);
        }

        return $query;
    }
}
