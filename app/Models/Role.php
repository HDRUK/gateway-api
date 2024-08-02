<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    /**
     * The table representing this model
     *
     * @var string
     */
    protected $table = 'roles';

    /**
     * Whether or not this model uses timestamps
     *
     * @var boolean
     */
    public $timestamps = true;

    protected $fillable = [
        'name',
        'enabled',
        'full_name',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    /**
     * The name representing this model record
     *
     * @var string
     */
    public string $name = '';

    /**
     * Whether or not this record is enabled
     *
     * @var boolean
     */
    public bool $enabled = true;

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_has_permissions');
    }
}
