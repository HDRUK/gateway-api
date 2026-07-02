<?php

namespace App\Models;

use App\Models\Base\BaseTypesenseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DataProviderColl extends BaseTypesenseModel
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

    public function shouldBeSearchable(): bool
    {
        return (bool) $this->enabled && $this->deleted_at === null;
    }

    public function toSearchableArray(): array
    {
        return [
            'id'      => (string) $this->id,
            'enabled' => (int) $this->enabled,
            'name'    => $this->name ?? '',
            'summary' => $this->summary ?? '',
            'service' => $this->service ?? '',
        ];
    }

    public function typesenseSearchParameters(): array
    {
        return [
            'query_by'         => 'name,summary,service',
            'query_by_weights' => '5,4,1',
        ];
    }

    public function typesenseCollectionSchema(): array
    {
        return [
            'name' => $this->searchableAs(),
            'fields' => [
                [ 'name' => 'id',       'type' => 'string', ],
                [ 'name' => 'enabled',  'type' => 'int32', ],
                [ 'name' => 'name',     'type' => 'string', 'infix' => true ],
                [ 'name' => 'summary',  'type' => 'string', 'infix' => true, 'optional' => true ],
                [ 'name' => 'service',  'type' => 'string', 'infix' => true, 'optional' => true ],
            ],
        ];
    }
}
