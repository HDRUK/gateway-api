<?php

namespace Tests\Fakes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Scout\Searchable;

/**
 * Minimal Scout model for reindex tests. Call createTable() in setUp; the
 * test transaction rolls the table back afterwards.
 */
class SearchableWidget extends Model
{
    use Searchable;

    protected $table = 'search_widgets';

    protected $guarded = [];

    protected $casts = ['is_hidden' => 'boolean'];

    public static function createTable(): void
    {
        Schema::create('search_widgets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();
        });
    }

    public function searchableAs(): string
    {
        return 'widgets';
    }

    public function shouldBeSearchable(): bool
    {
        return ! $this->is_hidden;
    }

    public function toSearchableArray(): array
    {
        return [
            'id'   => (string) $this->id,
            'name' => $this->name,
        ];
    }

    public function typesenseCollectionSchema(): array
    {
        return [
            'name'   => $this->searchableAs(),
            'fields' => [
                ['name' => 'id', 'type' => 'string'],
                ['name' => 'name', 'type' => 'string'],
            ],
        ];
    }
}
