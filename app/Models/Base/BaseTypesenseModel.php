<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 */
abstract class BaseTypesenseModel extends Model
{
    use Searchable;

    public function getScoutKeyName()
    {
        return 'id';
    }
    public function getScoutKey()
    {
        return (string) $this->id;
    }
    public function searchableAs()
    {
        return config('scout.prefix') . $this->getTable();
    }

    abstract public function typesenseCollectionSchema(): array;
    abstract public function typesenseSearchParameters(): array;
}
