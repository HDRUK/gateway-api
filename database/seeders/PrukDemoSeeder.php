<?php

namespace Database\Seeders;

use App\Http\Enums\TeamMemberOf;
use App\Jobs\LinkageExtraction;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\DatasetVersionHasSpatialCoverage;
use App\Models\SpatialCoverage;
use App\Models\Team;
use App\Models\User;
use App\Services\Gwdm\SourceSchema;
use Database\Seeders\Demo\PrukDemoGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use RuntimeException;

/**
 * Seeds fictional PRUK longitudinal population studies for demonstrating and
 * load-testing the combined search (keywords, variables and conditions):
 * 800 datasets by default, under a dozen fictional data custodians. See
 * Demo/PrukDemoGenerator for what's generated.
 *
 *     PRUK_DEMO_DATASETS=1000 php artisan db:seed --class=PrukDemoSeeder
 *     php artisan typesense:reindex datasets
 *     php artisan typesense:reindex variables
 *
 * Idempotent on pid: rerunning (at any size) updates the same datasets, and a
 * larger run adds to a smaller one. Saves without model events, so no
 * per-dataset jobs are queued and nothing syncs to Typesense - index once
 * afterwards, as above.
 */
class PrukDemoSeeder extends Seeder
{
    public const DEFAULT_COUNT = 800;

    public const MAX_COUNT = 2000;

    private const PARTNER = 'PRUK';

    private const CHUNK = 100;

    private const TEAM_NAMESPACE = 'b8a9c0e2-31f4-4d5e-8a7b-6c5d4e3f2a1b';

    private ?int $count = null;

    /** How many datasets to seed; otherwise the PRUK_DEMO_DATASETS environment variable, or 800. */
    public function count(int $count): static
    {
        $this->count = $count;

        return $this;
    }

    public function run(): void
    {
        $count = max(1, min(self::MAX_COUNT, $this->count ?? (int) (getenv('PRUK_DEMO_DATASETS') ?: self::DEFAULT_COUNT)));
        $items = (new PrukDemoGenerator())->generate($count);

        $userId = User::query()->orderBy('id')->value('id')
            ?? throw new RuntimeException('PrukDemoSeeder needs at least one user to own the datasets; run the base seeders first.');
        $regions = SpatialCoverage::pluck('id', 'region')->all();

        $this->command?->info("Seeding {$count} PRUK demo datasets...");

        Model::withoutEvents(function () use ($items, $userId, $regions) {
            $teams = $this->teams(array_values(array_unique(array_column($items, 'custodian'))));

            foreach (array_chunk($items, self::CHUNK) as $index => $chunk) {
                DB::transaction(function () use ($chunk, $teams, $userId, $regions) {
                    foreach ($chunk as $item) {
                        $this->seedDataset($item['envelope'], $teams[$item['custodian']], $userId, $regions);
                    }
                });

                $this->command?->info('  '.min(count($items), ($index + 1) * self::CHUNK).' / '.count($items));
            }
        });

        $this->command?->info('Done. Now index them: php artisan typesense:reindex datasets && php artisan typesense:reindex variables');
    }

    /**
     * The fictional custodians, created once and reused.
     *
     * @param  string[]  $names
     * @return array<string, int> team ids by name
     */
    private function teams(array $names): array
    {
        $ids = [];
        foreach ($names as $name) {
            $team = Team::firstOrCreate(['name' => $name], array_merge(Team::factory()->raw(), [
                'name' => $name,
                'pid' => Uuid::uuid5(self::TEAM_NAMESPACE, $name)->toString(),
                'enabled' => true,
                'is_admin' => false,
                'is_provider' => true,
                'is_question_bank' => false,
                'member_of' => TeamMemberOf::OTHER,
                'contact_point' => 'enquiries@example.org',
                'introduction' => 'A fictional data custodian holding PRUK demo datasets.',
            ]));
            $ids[$name] = $team->id;
        }

        return $ids;
    }

    private function seedDataset(array $envelope, int $teamId, int $userId, array $regions): void
    {
        $pid = $envelope['metadata']['required']['gatewayId'];
        $title = $envelope['metadata']['summary']['title'];

        $dataset = Dataset::withTrashed()->firstOrNew(['pid' => $pid]);
        if ($dataset->trashed()) {
            $dataset->restore();
        }
        $dataset->fill([
            'user_id' => $userId,
            'team_id' => $teamId,
            'partner_context' => self::PARTNER,
            'status' => Dataset::STATUS_ACTIVE,
            'create_origin' => Dataset::ORIGIN_MANUAL,
        ]);
        // Normally set by DatasetObserver, which doesn't run here.
        $dataset->active_date ??= now();
        $dataset->save();

        $version = DatasetVersion::withTrashed()->firstOrNew(['dataset_id' => $dataset->id, 'version' => 1]);
        if ($version->trashed()) {
            $version->restore();
        }
        $version->fill(array_merge(
            SourceSchema::declared(self::PARTNER, '1.0.0')->toColumns(),
            [
                'metadata' => $envelope,
                'title' => $title,
                'short_title' => $title,
                'gwdm_version' => '2.3',
                'user_id' => $userId,
            ],
        ));
        // Normally set by DatasetVersionObserver.
        $version->active_date ??= now();
        $version->provider_team_id = $teamId;
        $version->save();

        $this->mapCoverage($version, $envelope['metadata']['coverage']['spatial'], $regions);

        // Turns waveOf into junction rows. Parents are generated before their
        // waves, so a wave's parent always exists by now.
        LinkageExtraction::dispatchSync((string) $dataset->id, (string) $version->id);
    }

    /**
     * Records the version's geography for the location filter, by the same
     * rule as MetadataOnboard::mapCoverage(): any UK nation named in the
     * spatial text, every nation for "United Kingdom", otherwise the rest of
     * the world.
     */
    private function mapCoverage(DatasetVersion $version, string $spatial, array $regions): void
    {
        $text = strtolower($spatial);
        $uk = array_filter($regions, fn ($region) => $region !== 'Rest of the world', ARRAY_FILTER_USE_KEY);

        $ids = array_values(array_filter($uk, fn ($id, $region) => str_contains($text, strtolower($region)), ARRAY_FILTER_USE_BOTH));
        if (! $ids) {
            $ids = str_contains($text, 'united kingdom') ? array_values($uk) : array_filter([$regions['Rest of the world'] ?? null]);
        }

        DatasetVersionHasSpatialCoverage::where('dataset_version_id', $version->id)
            ->whereNotIn('spatial_coverage_id', $ids)
            ->delete();
        foreach ($ids as $id) {
            DatasetVersionHasSpatialCoverage::updateOrCreate([
                'dataset_version_id' => $version->id,
                'spatial_coverage_id' => $id,
            ]);
        }
    }
}
