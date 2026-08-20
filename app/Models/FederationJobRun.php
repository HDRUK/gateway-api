<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * @property-read string|null $started_at only populated on rows returned by executionsForFederation()
 * @property-read string|null $finished_at only populated on rows returned by executionsForFederation()
 */
class FederationJobRun extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Prunable;

    /**
     * Table associated with this model
     *
     * @var string
     */
    protected $table = 'federation_job_runs';

    /**
     * Indicates if this model is timestamped
     *
     * @var bool
     */
    public $timestamps = true;

    protected $fillable = [
        'team_id',
        'federation_id',
        'pid',
        'job_uuid',
        'status',
        'details',
        'job_attempts',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    /**
     * Latest recorded row per dataset (pid) for one federation execution.
     *
     * @return Collection<int, FederationJobRun>
     */
    public static function latestPerPidForExecution(int $federationId, string $jobUuid): Collection
    {
        return static::where('federation_id', $federationId)
            ->where('job_uuid', $jobUuid)
            ->whereIn('id', function ($query) use ($federationId, $jobUuid) {
                $query->selectRaw('MAX(id)')
                    ->from((new self())->getTable())
                    ->where('federation_id', $federationId)
                    ->where('job_uuid', $jobUuid)
                    ->groupBy('pid');
            })
            ->get();
    }

    /**
     * Distinct executions (job_uuid groups) for a federation, most recent first.
     *
     * @return LengthAwarePaginator<int, FederationJobRun>
     */
    public static function executionsForFederation(int $federationId, int $perPage): LengthAwarePaginator
    {
        return static::where('federation_id', $federationId)
            ->select('job_uuid')
            ->selectRaw('MIN(created_at) as started_at')
            ->selectRaw('MAX(created_at) as finished_at')
            ->groupBy('job_uuid')
            ->orderByDesc('started_at')
            ->paginate($perPage, ['*'], 'page');
    }

    /**
     * @param non-empty-array<int, int> $federationIds
     * @return Collection<int, string> federation_id => last_run_at
     */
    public static function latestRunTimesForFederationIds(array $federationIds): Collection
    {
        return static::select('federation_id')
            ->selectRaw('MAX(created_at) as last_run_at')
            ->whereIn('federation_id', $federationIds)
            ->groupBy('federation_id')
            ->get()
            ->pluck('last_run_at', 'federation_id');
    }

    /**
     * @return array<int, array{schema: ?string, message: string}>
     */
    public function errorMessages(): array
    {
        $raw = data_get($this, 'details.message', '');

        if (is_string($raw)) {
            return [['schema' => null, 'message' => $raw]];
        }

        if (is_array($raw)) {
            $items = isset($raw['message']) && is_array($raw['message']) ? $raw['message'] : $raw;

            $entries = [];
            foreach ($items as $item) {
                if (is_array($item) && isset($item['errors']) && is_array($item['errors'])) {
                    $schema = ($item['name'] ?? '?') . '/' . ($item['version'] ?? '?');
                    foreach ($item['errors'] as $error) {
                        $entries[] = ['schema' => $schema, 'message' => $error['message'] ?? 'Unknown validation error'];
                    }
                }
            }

            if (!empty($entries)) {
                return $entries;
            }

            if (isset($raw['traser_message'])) {
                return [['schema' => null, 'message' => (string) $raw['traser_message']]];
            }
        }

        return [['schema' => null, 'message' => 'An error occurred while processing this dataset.']];
    }
}
