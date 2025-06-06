<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DataAccessTemplate extends Model
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use Prunable;

    /**
     * The table associated with the model
     *
     * @var string
     */
    protected $table = 'dar_templates';

    protected $fillable = [
        'team_id',
        'user_id',
        'published',
        'locked',
        'template_type',
    ];

    protected $casts = [
        'published' => 'boolean',
        'locked' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(DataAccessTemplateHasQuestion::class, 'template_id');
    }

    public function files(): HasManyThrough
    {
        return $this->hasManyThrough(
            Upload::class,
            DataAccessTemplateHasFile::class,
            'template_id',
            'id',
            'id',
            'upload_id'
        );
    }
}
