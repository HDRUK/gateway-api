<?php

namespace Tests\Unit\Services\Search;

use App\Exceptions\TypesenseReindexException;
use App\Services\Search\CollectionReindexer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Fakes\FakeTypesenseService;
use Tests\Fakes\SearchableWidget;
use Tests\TestCase;

class CollectionReindexerTest extends TestCase
{
    private FakeTypesenseService $typesense;

    private CollectionReindexer $reindexer;

    protected function setUp(): void
    {
        parent::setUp();

        SearchableWidget::createTable();
        Carbon::setTestNow('2026-11-09 10:00:00');

        $this->typesense = new FakeTypesenseService();
        $this->reindexer = new CollectionReindexer($this->typesense);
    }

    public function tearDown(): void
    {
        Carbon::setTestNow();
        SearchableWidget::$onEligibilityCheck = null;

        parent::tearDown();
    }

    private function widget(string $name, bool $hidden = false): SearchableWidget
    {
        return SearchableWidget::withoutSyncingToSearch(
            fn () => SearchableWidget::create(['name' => $name, 'is_hidden' => $hidden])
        );
    }

    /** @return array<string, string> id => name, for easy assertions. */
    private function indexed(string $collection = 'widgets'): array
    {
        return array_map(fn (array $doc) => $doc['name'], $this->typesense->storedDocuments($collection));
    }

    private function legacySchema(): array
    {
        return (new SearchableWidget())->typesenseCollectionSchema();
    }

    // -------------------------------------------------------------------------
    // rebuild(): build a new collection, then swap the alias onto it
    // -------------------------------------------------------------------------

    public function test_rebuild_builds_a_versioned_collection_behind_the_alias(): void
    {
        $a = $this->widget('Alpha');
        $this->widget('Hidden', hidden: true);

        $result = $this->reindexer->rebuild(SearchableWidget::class);

        $target = $this->typesense->aliasTarget('widgets');
        $this->assertMatchesRegularExpression('/^widgets_\d{20}$/', $target);
        $this->assertSame($target, $result['collection']);
        $this->assertSame([$target], $this->typesense->collectionNames(), 'no physical collection named after the alias');
        $this->assertSame([(string) $a->id => 'Alpha'], $this->indexed());
        $this->assertSame(1, $result['documents']);
    }

    public function test_rebuild_replaces_a_legacy_physical_collection_with_the_alias(): void
    {
        $this->typesense->createCollection($this->legacySchema());
        $this->typesense->upsertDocument('widgets', ['id' => '999', 'name' => 'Stale']);
        $a = $this->widget('Alpha');

        $events = [];
        $this->reindexer->rebuild(SearchableWidget::class, function (string $event, array $context) use (&$events) {
            $events[] = $event;
        });

        $target = $this->typesense->aliasTarget('widgets');
        $this->assertNotNull($target);
        $this->assertSame([$target], $this->typesense->collectionNames());
        $this->assertSame([(string) $a->id => 'Alpha'], $this->indexed());
        $this->assertContains('dropping_legacy', $events);
    }

    public function test_the_live_collection_keeps_serving_until_the_swap(): void
    {
        $this->widget('Alpha');
        $this->reindexer->rebuild(SearchableWidget::class);
        $previous = $this->typesense->aliasTarget('widgets');

        Carbon::setTestNow('2026-11-09 11:00:00');
        $this->widget('Beta');

        $seenDuringBuild = null;
        $this->reindexer->rebuild(SearchableWidget::class, function (string $event) use (&$seenDuringBuild) {
            if ($event === 'imported') {
                $seenDuringBuild = $this->indexed();
            }
        });

        $this->assertSame(['Alpha'], array_values($seenDuringBuild), 'searches hit the old collection mid-build');
        $this->assertSame(['Alpha', 'Beta'], array_values($this->indexed()));
        $this->assertNotSame($previous, $this->typesense->aliasTarget('widgets'));
        $this->assertNotContains($previous, $this->typesense->collectionNames(), 'previous collection dropped after the swap');
    }

    public function test_rebuild_uses_the_models_current_schema(): void
    {
        $this->typesense->createCollection(['name' => 'widgets', 'fields' => [['name' => 'id', 'type' => 'string']]]);
        $this->widget('Alpha');

        $this->reindexer->rebuild(SearchableWidget::class);

        $fields = array_column($this->typesense->getCollection('widgets')['fields'], 'type', 'name');
        $this->assertSame(['id' => 'string', 'name' => 'string'], $fields);
    }

    public function test_rebuild_catches_up_on_records_changed_during_the_build(): void
    {
        $a = $this->widget('Alpha');

        $this->reindexer->rebuild(SearchableWidget::class, function (string $event) use ($a) {
            if ($event === 'imported') {
                // An edit made while the import ran; Scout's live write would
                // have gone to the old collection through the alias.
                Carbon::setTestNow('2026-11-09 10:00:05');
                SearchableWidget::withoutSyncingToSearch(fn () => $a->update(['name' => 'Alpha v2']));
            }
        });

        $this->assertSame([(string) $a->id => 'Alpha v2'], $this->indexed());
    }

    public function test_a_failed_rebuild_leaves_the_live_collection_untouched(): void
    {
        $this->widget('Alpha');
        $this->reindexer->rebuild(SearchableWidget::class);
        $live = $this->typesense->aliasTarget('widgets');

        Carbon::setTestNow('2026-11-09 11:00:00');
        $this->widget('Broken');
        $this->typesense->rejectDocumentsWhen(fn (array $doc) => $doc['name'] === 'Broken' ? 'bad field' : null);

        try {
            $this->reindexer->rebuild(SearchableWidget::class);
            $this->fail('Expected the rebuild to fail');
        } catch (TypesenseReindexException $e) {
            $this->assertStringContainsString('bad field', $e->getMessage());
        }

        $this->assertSame($live, $this->typesense->aliasTarget('widgets'));
        $this->assertSame([$live], $this->typesense->collectionNames(), 'half-built collection dropped');
        $this->assertSame(['Alpha'], array_values($this->indexed()));
    }

    public function test_rebuild_sweeps_collections_orphaned_by_crashed_runs(): void
    {
        $this->typesense->createCollection(['name' => 'widgets_20260101000000000000', 'fields' => []]);
        $this->typesense->createCollection(['name' => 'widgets_archive', 'fields' => []]);
        $this->typesense->createCollection(['name' => 'gadgets_20260101000000000000', 'fields' => []]);

        $this->reindexer->rebuild(SearchableWidget::class);

        $names = $this->typesense->collectionNames();
        $this->assertNotContains('widgets_20260101000000000000', $names);
        $this->assertContains('widgets_archive', $names);
        $this->assertContains('gadgets_20260101000000000000', $names);
    }

    public function test_a_reindex_already_running_for_the_entity_is_refused(): void
    {
        $lock = Cache::lock('typesense-reindex:widgets', 60);
        $lock->get();

        try {
            $this->expectException(TypesenseReindexException::class);
            $this->expectExceptionMessage('already running');

            $this->reindexer->rebuild(SearchableWidget::class);
        } finally {
            $lock->release();
        }
    }

    public function test_the_lock_is_released_when_a_rebuild_fails(): void
    {
        $this->widget('Broken');
        $this->typesense->rejectDocumentsWhen(fn () => 'bad field');

        try {
            $this->reindexer->rebuild(SearchableWidget::class);
        } catch (TypesenseReindexException) {
        }

        $this->assertTrue(Cache::lock('typesense-reindex:widgets', 1)->get());
    }

    /**
     * Runs a second rebuild over an existing alias, calling $duringBuild once
     * the main import has finished, while the old collection is still live.
     */
    private function rebuildWith(callable $duringBuild): void
    {
        $this->reindexer->rebuild(SearchableWidget::class);
        Carbon::setTestNow('2026-11-09 11:00:00');

        $this->reindexer->rebuild(SearchableWidget::class, function (string $event) use ($duringBuild) {
            if ($event === 'imported') {
                Carbon::setTestNow('2026-11-09 11:00:05');
                $duringBuild();
            }
        });
    }

    /** What Scout's observer does when a record stops being searchable: delete it via the alias. */
    private function scoutRemoves(SearchableWidget $widget): void
    {
        $this->typesense->deleteDocumentsByIds('widgets', [(string) $widget->id]);
    }

    public function test_rebuild_drops_records_soft_deleted_during_the_build(): void
    {
        $a = $this->widget('Alpha');
        $b = $this->widget('Beta');

        $this->rebuildWith(function () use ($b) {
            SearchableWidget::withoutSyncingToSearch(fn () => $b->delete());
            $this->scoutRemoves($b);
        });

        $this->assertSame([(string) $a->id => 'Alpha'], $this->indexed());
    }

    public function test_rebuild_drops_records_that_left_the_index_without_their_own_row_changing(): void
    {
        // e.g. a DatasetVersion superseded by a newer version: its eligibility
        // changes but its updated_at doesn't.
        $a = $this->widget('Alpha');
        $b = $this->widget('Beta');

        $this->rebuildWith(function () use ($b) {
            DB::table('search_widgets')->where('id', $b->id)->update(['is_hidden' => true]);
            $this->scoutRemoves($b);
        });

        $this->assertSame([(string) $a->id => 'Alpha'], $this->indexed());
    }

    public function test_rebuild_keeps_records_scout_removed_that_are_eligible_again(): void
    {
        $a = $this->widget('Alpha');

        // Removed live then restored by a write that didn't reach Typesense:
        // the database, not the live index, decides.
        $this->rebuildWith(fn () => $this->scoutRemoves($a));

        $this->assertSame([(string) $a->id => 'Alpha'], $this->indexed());
    }

    /**
     * Runs $hook on every eligibility check made after the catch-up, i.e.
     * by the reconcile step only.
     */
    private function rebuildObservingReconcile(callable $duringBuild, callable $hook): void
    {
        Carbon::setTestNow('2026-11-09 11:00:00');

        $this->reindexer->rebuild(SearchableWidget::class, function (string $event) use ($duringBuild, $hook) {
            if ($event === 'imported') {
                Carbon::setTestNow('2026-11-09 11:00:05');
                $duringBuild();
            }
            if ($event === 'caught_up') {
                SearchableWidget::$onEligibilityCheck = $hook;
            }
            if ($event === 'reconciled') {
                SearchableWidget::$onEligibilityCheck = null;
            }
        });
    }

    public function test_reconcile_only_rechecks_records_removed_from_live_during_the_build(): void
    {
        // Live is missing most records (wiped, or never fully indexed): those
        // weren't removed during the build, so they cost no re-check.
        $a = $this->widget('Alpha');
        $b = $this->widget('Beta');
        $c = $this->widget('Gamma');
        $d = $this->widget('Delta');
        $this->reindexer->rebuild(SearchableWidget::class);
        $this->typesense->deleteDocumentsByIds('widgets', [(string) $c->id, (string) $d->id]);

        $checked = [];
        $this->rebuildObservingReconcile(
            function () use ($b) {
                DB::table('search_widgets')->where('id', $b->id)->update(['is_hidden' => true]);
                $this->scoutRemoves($b);
            },
            function (SearchableWidget $widget) use (&$checked) {
                $checked[] = $widget->name;
            },
        );

        $this->assertSame(['Beta'], $checked);
        $this->assertEqualsCanonicalizing(['Alpha', 'Gamma', 'Delta'], array_values($this->indexed()));
    }

    public function test_reconcile_against_an_empty_live_collection_rechecks_nothing(): void
    {
        $this->widget('Alpha');
        $this->widget('Beta');
        $this->reindexer->rebuild(SearchableWidget::class);
        $this->typesense->deleteDocumentsByIds('widgets', array_keys($this->indexed()));

        $checks = 0;
        $this->rebuildObservingReconcile(fn () => null, function () use (&$checks) {
            $checks++;
        });

        $this->assertSame(0, $checks);
        $this->assertCount(2, $this->indexed());
    }

    public function test_the_lock_is_kept_alive_through_a_long_reconcile(): void
    {
        $widgets = [$this->widget('Alpha'), $this->widget('Beta'), $this->widget('Gamma')];
        $this->reindexer->rebuild(SearchableWidget::class);
        config(['typesense.reindex.lock_seconds' => 10, 'scout.chunk.searchable' => 1]);

        $this->rebuildObservingReconcile(
            function () use ($widgets) {
                foreach ($widgets as $widget) {
                    $this->scoutRemoves($widget);
                }
            },
            // 3 checks x 8s = 24s, well past the 10s lock.
            fn () => Carbon::setTestNow(Carbon::now()->addSeconds(8)),
        );

        $this->assertCount(3, $this->indexed(), 'all three are still eligible');
    }

    public function test_the_lock_is_kept_alive_while_a_long_rebuild_runs(): void
    {
        config(['typesense.reindex.lock_seconds' => 10, 'scout.chunk.searchable' => 1]);
        $this->widget('Alpha');
        $this->widget('Beta');
        $this->widget('Gamma');

        $heldAtEnd = null;
        $this->reindexer->rebuild(SearchableWidget::class, function (string $event) use (&$heldAtEnd) {
            if ($event === 'chunk') {
                // 3 chunks x 8s = 24s, well past the 10s lock.
                Carbon::setTestNow(Carbon::now()->addSeconds(8));
            }
            if ($event === 'imported') {
                $heldAtEnd = ! Cache::lock('typesense-reindex:widgets', 1)->get();
            }
        });

        $this->assertTrue($heldAtEnd, 'another run could take the lock mid-build');
    }

    public function test_a_rebuild_that_loses_its_lock_stops_without_swapping(): void
    {
        config(['typesense.reindex.lock_seconds' => 10, 'scout.chunk.searchable' => 1]);
        $this->widget('Alpha');
        $this->widget('Beta');
        $this->reindexer->rebuild(SearchableWidget::class);
        $live = $this->typesense->aliasTarget('widgets');

        Carbon::setTestNow('2026-11-09 11:00:00');

        try {
            $this->reindexer->rebuild(SearchableWidget::class, function (string $event) {
                if ($event === 'chunk') {
                    // Stalled past the lock's lifetime; another run took over.
                    Carbon::setTestNow(Carbon::now()->addSeconds(11));
                    Cache::lock('typesense-reindex:widgets', 10)->get();
                }
            });
            $this->fail('Expected the rebuild to stop');
        } catch (TypesenseReindexException $e) {
            $this->assertStringContainsString('lost its lock', $e->getMessage());
        }

        $this->assertSame($live, $this->typesense->aliasTarget('widgets'));
        $this->assertSame([$live], $this->typesense->collectionNames(), 'half-built collection dropped');
    }

    // -------------------------------------------------------------------------
    // refresh(): upsert into the live collection and prune what's gone
    // -------------------------------------------------------------------------

    public function test_refresh_upserts_into_the_live_collection_in_place(): void
    {
        $a = $this->widget('Alpha');
        $this->reindexer->rebuild(SearchableWidget::class);
        $live = $this->typesense->aliasTarget('widgets');

        SearchableWidget::withoutSyncingToSearch(fn () => $a->update(['name' => 'Alpha v2']));
        $b = $this->widget('Beta');

        $result = $this->reindexer->refresh(SearchableWidget::class);

        $this->assertSame($live, $this->typesense->aliasTarget('widgets'));
        $this->assertSame([$live], $this->typesense->collectionNames());
        $this->assertSame([(string) $a->id => 'Alpha v2', (string) $b->id => 'Beta'], $this->indexed());
        $this->assertSame(2, $result['documents']);
    }

    public function test_refresh_works_on_a_legacy_physical_collection(): void
    {
        $this->typesense->createCollection($this->legacySchema());
        $a = $this->widget('Alpha');

        $this->reindexer->refresh(SearchableWidget::class);

        $this->assertNull($this->typesense->aliasTarget('widgets'));
        $this->assertSame([(string) $a->id => 'Alpha'], $this->indexed());
    }

    public function test_refresh_prunes_documents_that_are_no_longer_searchable(): void
    {
        $a = $this->widget('Alpha');
        $b = $this->widget('Beta');
        $this->reindexer->rebuild(SearchableWidget::class);

        SearchableWidget::withoutSyncingToSearch(fn () => $b->update(['is_hidden' => true]));
        $this->typesense->upsertDocument('widgets', ['id' => '999', 'name' => 'Deleted row']);

        $result = $this->reindexer->refresh(SearchableWidget::class);

        $this->assertSame([(string) $a->id => 'Alpha'], $this->indexed());
        $this->assertSame(2, $result['pruned']);
    }

    public function test_refresh_keeps_records_created_after_the_import_pass(): void
    {
        $this->widget('Alpha');
        $this->reindexer->rebuild(SearchableWidget::class);

        $late = null;
        $this->reindexer->refresh(SearchableWidget::class, function (string $event) use (&$late) {
            if ($event === 'imported') {
                // Created after the import's chunk loop finished; Scout's
                // observer writes it to the live collection.
                $late = $this->widget('Late');
                $this->typesense->upsertDocument('widgets', $late->toSearchableArray());
            }
        });

        $this->assertArrayHasKey((string) $late->id, $this->indexed());
    }

    public function test_refresh_refuses_when_the_live_schema_has_drifted(): void
    {
        $this->typesense->createCollection(['name' => 'widgets', 'fields' => [
            ['name' => 'id', 'type' => 'string'],
            ['name' => 'name', 'type' => 'int32'],
        ]]);
        $this->typesense->upsertDocument('widgets', ['id' => '999', 'name' => 1]);
        $this->widget('Alpha');

        try {
            $this->reindexer->refresh(SearchableWidget::class);
            $this->fail('Expected schema drift to be refused');
        } catch (TypesenseReindexException $e) {
            $this->assertStringContainsString('name', $e->getMessage());
            $this->assertStringContainsString('without --upsert', $e->getMessage());
        }

        $this->assertSame(['999' => 1], $this->indexed(), 'nothing written or pruned');
    }

    public function test_refresh_without_a_collection_falls_back_to_a_rebuild(): void
    {
        $a = $this->widget('Alpha');

        $this->reindexer->refresh(SearchableWidget::class);

        $this->assertNotNull($this->typesense->aliasTarget('widgets'));
        $this->assertSame([(string) $a->id => 'Alpha'], $this->indexed());
    }

    public function test_a_refresh_with_rejected_documents_does_not_prune(): void
    {
        $this->widget('Alpha');
        $this->reindexer->rebuild(SearchableWidget::class);
        $this->typesense->upsertDocument('widgets', ['id' => '999', 'name' => 'Deleted row']);
        $this->typesense->rejectDocumentsWhen(fn (array $doc) => $doc['name'] === 'Alpha' ? 'bad field' : null);

        try {
            $this->reindexer->refresh(SearchableWidget::class);
            $this->fail('Expected the refresh to fail');
        } catch (TypesenseReindexException) {
        }

        $this->assertArrayHasKey('999', $this->indexed());
    }
}
