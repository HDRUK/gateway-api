<?php

namespace App\Models;

use App\Models\Tag;
use App\Models\Team;
use App\Models\User;
use App\Models\Permission;
use App\Http\Traits\WithJwtUser;
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
    use WithJwtUser;

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

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
