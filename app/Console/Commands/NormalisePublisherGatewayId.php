<?php

namespace App\Console\Commands;

use App\Models\DatasetVersion;
use App\Models\Team;
use Illuminate\Console\Command;

/**
 * One-off data fix: normalise summary.publisher gateway identifiers stored in
 * dataset_versions so that gatewayId (GWDM 2.x) / publisherId (legacy < 1.1)
 * hold a team PID rather than an internal primary key.
 *
 * Historically these were sometimes populated with a raw team id (chiefly via
 * the V1 onboarding path, whose value traced back to the frontend
 * dataCustodian.identifier = team.id bug). Consumers cope by guessing
 * (is_numeric -> id, else pid); this command makes the stored data canonical.
 *
 * Covers both storage forms:
 *   - Snapshot rows (patch IS NULL): the full GWDM envelope in `metadata`.
 *   - Delta rows (patch present): RFC-6902 patch ops that write the publisher
 *     id path (paths are relative to the GWDM object, e.g.
 *     /summary/publisher/gatewayId — see DatasetService::computePatch/
 *     reconstructGwdmMetadata).
 *
 * `original_metadata` is deliberately left untouched (audit trail). Intended to
 * run as a post-migration deployment step (registered in RunPostMigrations
 * before the datasets reindex, so Elasticsearch picks up corrected values).
 */
class NormalisePublisherGatewayId extends Command
{
    protected $signature = 'app:normalise-publisher-gateway-id
                            {--dry-run : Report what would change without writing}
                            {--chunk=200 : Number of dataset_versions per chunk}';

    protected $description = 'Normalise summary.publisher gatewayId/publisherId in dataset_versions to team PIDs (snapshots + delta patch ops).';

    /** Publisher id keys across GWDM shapes (2.x gatewayId, legacy publisherId). */
    private const ID_KEYS = ['gatewayId', 'publisherId'];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunk = max(1, (int) $this->option('chunk'));

        // Preload teams once for in-memory id-or-pid resolution (mirrors the
        // approach in DatasetHydrator). Include soft-deleted teams so metadata
        // referencing a since-archived team can still be normalised.
        $teams = Team::withTrashed()->get(['id', 'pid', 'name']);
        $byId = $teams->keyBy('id');
        $byPid = $teams->keyBy('pid');

        $resolvePid = function ($gatewayId) use ($byId, $byPid): ?string {
            if ($gatewayId === null || $gatewayId === '') {
                return null;
            }
            $team = is_numeric($gatewayId)
                ? $byId->get((int) $gatewayId)
                : $byPid->get((string) $gatewayId);

            return $team?->pid;
        };

        $stats = ['scanned' => 0, 'snapshots' => 0, 'deltaOps' => 0, 'unresolved' => 0];

        DatasetVersion::withTrashed()->chunkById($chunk, function ($versions) use ($resolvePid, $dryRun, &$stats) {
            foreach ($versions as $dv) {
                $stats['scanned']++;

                if ($dv->patch === null) {
                    $this->fixSnapshot($dv, $resolvePid, $dryRun, $stats);
                } else {
                    $this->fixDelta($dv, $resolvePid, $dryRun, $stats);
                }
            }
        });

        $mode = $dryRun ? 'DRY-RUN (no writes)' : 'APPLIED';
        $this->info(sprintf(
            '[%s] scanned=%d snapshotsFixed=%d deltaOpsFixed=%d unresolved=%d',
            $mode,
            $stats['scanned'],
            $stats['snapshots'],
            $stats['deltaOps'],
            $stats['unresolved'],
        ));

        return self::SUCCESS;
    }

    /**
     * Snapshot row: the publisher lives in metadata.metadata.summary.publisher.
     */
    private function fixSnapshot(DatasetVersion $dv, callable $resolvePid, bool $dryRun, array &$stats): void
    {
        $envelope = $dv->metadata;
        if (! is_array($envelope) || ! isset($envelope['metadata']['summary']['publisher'])) {
            return;
        }

        $publisher = $envelope['metadata']['summary']['publisher'];
        if (! is_array($publisher)) {
            return;
        }

        $result = $this->normalisePublisherArray($publisher, $resolvePid, $dv, $stats);
        if (! $result['changed']) {
            return;
        }

        $stats['snapshots']++;
        if (! $dryRun) {
            $envelope['metadata']['summary']['publisher'] = $result['publisher'];
            $dv->metadata = $envelope;
            $dv->saveQuietly();
        }
    }

    /**
     * Delta row: publisher only appears in RFC-6902 patch ops (metadata is []).
     */
    private function fixDelta(DatasetVersion $dv, callable $resolvePid, bool $dryRun, array &$stats): void
    {
        $patch = $dv->patch;
        if (! is_array($patch)) {
            return;
        }

        $changed = false;
        foreach ($patch as $i => $op) {
            if (! is_array($op) || ! isset($op['op'], $op['path']) || ! array_key_exists('value', $op)) {
                continue;
            }
            if (! in_array($op['op'], ['add', 'replace'], true)) {
                continue;
            }

            // Case 1: op targets the id leaf directly (value is a scalar id/pid).
            if ($this->isPublisherIdLeafPath($op['path'])) {
                $pid = $resolvePid($op['value']);
                if ($pid === null) {
                    $this->noteUnresolved($op['value'], $dv, $stats, $op['path']);

                    continue;
                }
                if ((string) $op['value'] !== $pid) {
                    $patch[$i]['value'] = $pid;
                    $changed = true;
                    $stats['deltaOps']++;
                }

                continue;
            }

            // Case 2: op replaces the whole publisher object (value is an array).
            if ($op['path'] === '/summary/publisher' && is_array($op['value'])) {
                $result = $this->normalisePublisherArray($op['value'], $resolvePid, $dv, $stats);
                if ($result['changed']) {
                    $patch[$i]['value'] = $result['publisher'];
                    $changed = true;
                    $stats['deltaOps']++;
                }
            }
        }

        if ($changed && ! $dryRun) {
            $dv->patch = $patch;
            $dv->saveQuietly();
        }
    }

    /**
     * Normalise each id key present on a publisher array to the resolved team
     * pid. Returns ['changed' => bool, 'publisher' => array].
     */
    private function normalisePublisherArray(array $publisher, callable $resolvePid, DatasetVersion $dv, array &$stats): array
    {
        $changed = false;
        foreach (self::ID_KEYS as $idKey) {
            if (! array_key_exists($idKey, $publisher)) {
                continue;
            }
            $current = $publisher[$idKey];
            $pid = $resolvePid($current);
            if ($pid === null) {
                $this->noteUnresolved($current, $dv, $stats, "summary.publisher.$idKey");

                continue;
            }
            if ((string) $current !== $pid) {
                $publisher[$idKey] = $pid;
                $changed = true;
            }
        }

        return ['changed' => $changed, 'publisher' => $publisher];
    }

    private function isPublisherIdLeafPath(string $path): bool
    {
        return in_array($path, [
            '/summary/publisher/gatewayId',
            '/summary/publisher/publisherId',
        ], true);
    }

    private function noteUnresolved(mixed $value, DatasetVersion $dv, array &$stats, string $where): void
    {
        if ($value === null || $value === '') {
            return;
        }
        $stats['unresolved']++;
        $this->warn("Unresolved publisher id '{$value}' ({$where}) on dataset_version id={$dv->id} — left unchanged");
    }
}
