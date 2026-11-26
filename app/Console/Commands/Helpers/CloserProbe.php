<?php

namespace App\Console\Commands\Helpers;

use App\Modules\Closer\CloserClient;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;

/**
 * Spike helper (GAT-9844): fetch one CLOSER item, its version history and
 * everything under it, save the raw JSON, and summarise children by type.
 */
class CloserProbe extends Command
{
    protected $signature = 'closer:probe
        {identifier : agency/id, urn:ddi:agency:id[:version], or a Discovery item URL}';

    protected $description = 'Spike: save raw CLOSER responses for an item and summarise what it contains';

    public function handle(CloserClient $closer): int
    {
        try {
            ['agency' => $agency, 'id' => $id] = CloserClient::parseIdentifier($this->argument('identifier'));

            $item = $closer->get("item/{$agency}/{$id}");
            if (! $item->successful()) {
                return $this->failWith('item', $item);
            }

            $history = $closer->get("item/{$agency}/{$id}/history");
            $version = $item->json('Version') ?? $this->latestVersion($history);

            $set = $version !== null ? $closer->get("set/{$agency}/{$id}/{$version}/typed") : null;
        } catch (InvalidArgumentException|RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $dir = "closer-probe/{$agency}_{$id}";
        $this->save("{$dir}/item.json", $item);
        $this->save("{$dir}/history.json", $history);
        if ($set !== null) {
            $this->save("{$dir}/set.json", $set);
        }

        $this->info("Item: {$agency}/{$id}");
        $this->line('Top-level keys: '.implode(', ', array_keys($item->json() ?? [])));
        $this->line('ItemType: '.($item->json('ItemType') ?? '-'));
        $this->line('Version: '.($version ?? '-'));
        $this->line('VersionDate: '.($item->json('VersionDate') ?? '-'));
        $this->line($history->successful()
            ? 'History: '.count($history->json() ?? []).' versions'
            : "History: HTTP {$history->status()}");

        $this->summariseSet($set);
        $this->info('Raw responses saved to '.Storage::disk('local')->path($dir));

        return self::SUCCESS;
    }

    private function summariseSet(?Response $set): void
    {
        if ($set === null) {
            $this->warn('Set: skipped, no version known');

            return;
        }
        if (! $set->successful()) {
            $this->warn("Set: HTTP {$set->status()}");

            return;
        }

        $children = $set->json() ?? [];
        $this->line('Children: '.count($children));

        // Colectica's typed set appears to be (identifier, item type) pairs;
        // the shape isn't confirmed, so report anything else instead of guessing.
        $counts = [];
        foreach ($children as $child) {
            $type = is_array($child) ? ($child['Item2'] ?? $child['ItemType'] ?? null) : null;
            if (! is_string($type)) {
                $keys = is_array($child) ? implode(', ', array_keys($child)) : gettype($child);
                $this->warn("Unrecognised child shape, keys: {$keys}");

                return;
            }
            $counts[$type] = ($counts[$type] ?? 0) + 1;
        }

        arsort($counts);
        foreach ($counts as $type => $count) {
            $this->line("  {$type}: {$count}");
        }
    }

    private function latestVersion(Response $history): ?int
    {
        $versions = array_filter(array_column($history->json() ?? [], 'Version'), 'is_numeric');

        return $versions ? (int) max($versions) : null;
    }

    private function save(string $path, Response $response): void
    {
        Storage::disk('local')->put($path, json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function failWith(string $what, Response $response): int
    {
        $this->error("{$what}: HTTP {$response->status()}");

        return self::FAILURE;
    }
}
