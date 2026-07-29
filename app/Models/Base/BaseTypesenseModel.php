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

    // Scout's Searchable trait registers its own ModelObserver that fires on
    // every saved/deleted event and calls these two methods to sync to Typesense.
    // That happens unconditionally — regardless of any feature flag — so we gate
    // here rather than only in the search path. Without this, saving a Collection
    // on an environment where the flag is off still attempts a Typesense upsert
    // and surfaces a "forbidden" API-key error to the user.

    public static function queueMakeSearchable($models): void
    {
        if (!Feature::active('TypesenseSearch')) {
            return;
        }

        parent::queueMakeSearchable($models);
    }

    public static function queueRemoveFromSearch($models): void
    {
        if (!Feature::active('TypesenseSearch')) {
            return;
        }

        parent::queueRemoveFromSearch($models);
    }

    abstract public function toSearchableArray(): array;
    abstract public function typesenseCollectionSchema(): array;
    abstract public function typesenseSearchParameters(): array;
}
