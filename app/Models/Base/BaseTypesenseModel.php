<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Model;
use Laravel\Pennant\Feature as PennantFeature;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 */
abstract class BaseTypesenseModel extends Model
{
    use Searchable {
        // Alias the trait originals so we can call them from our overrides below
        // while still intercepting before any Typesense connection is attempted.
        Searchable::queueMakeSearchable as traitQueueMakeSearchable;
        Searchable::queueRemoveFromSearch as traitQueueRemoveFromSearch;
    }

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
    // here rather than only in the search path. Without this, saving a model on
    // an environment where the flag is off still attempts a Typesense upsert and
    // surfaces a "forbidden" API-key error to the user.

    public function queueMakeSearchable($models): void
    {
        if (!PennantFeature::active('TypesenseSearch')) {
            return;
        }

        $this->traitQueueMakeSearchable($models);
    }

    public function queueRemoveFromSearch($models): void
    {
        if (!PennantFeature::active('TypesenseSearch')) {
            return;
        }

        $this->traitQueueRemoveFromSearch($models);
    }

    abstract public function toSearchableArray(): array;
    abstract public function typesenseCollectionSchema(): array;
    abstract public function typesenseSearchParameters(): array;
}
