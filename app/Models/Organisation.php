<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Organisation extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'organisations';

    /**
     * Indicates if the model should be timestamped
     * 
     * @var bool
     */
    public $timestamps = true;

    protected $fillable = [
        'name', 
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    /**
     * Represents the name of this organisation
     * 
     * @var string
     */
    private $name = '';

    /**
     * Whether or not this organisation is enabled
     * 
     * @var boolean
     */
    private $enabled = false;

    /**
     * The users that belong to the organisation.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_has_organisation');
    }
}
