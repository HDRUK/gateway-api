<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DataProviderColl extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'enabled',
        'name',
        'summary',
        'img_url',
        'url',
        'service',
    ];

    /**
     * Table associated with this model
     *
     * @var string
    */
    protected $table = 'data_provider_colls';

    /**
     * Indicates if this model is timestamped
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Specifically requests that Laravel cast these vars
     */
    protected $casts = [
        'enabled' => 'boolean',
    ];

    /**
     * Indicates whether this model is enabled or disabled
     *
     * @var bool
     */
    private $enabled = false;

    /**
     * Represents the name of this DataProvider
     *
     * @var string
     */
    private $name = '';

    /**
     * Represents the image url for this DataProvider
     *
     * @var string
     */
    private $img_url = '';

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(
            Team::class,
            'data_provider_coll_has_teams'
        );
    }
}
