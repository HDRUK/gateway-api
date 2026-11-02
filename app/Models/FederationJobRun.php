<?php

namespace App\Models;

use App\Enums\FederationJobRunStatus;
use Illuminate\Database\Eloquent\Builder;
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
        'status' => FederationJobRunStatus::class,
    ];

    public static function startRun(int $teamId, int $federationId, string $jobUuid): self
    {
        return static::create([
            'team_id' => $teamId,
            'federation_id' => $federationId,
            'pid' => null,
            'job_uuid' => $jobUuid,
            'status' => null,
            'details' => ['message' => 'RUN'],
            'job_attempts' => 1,
        ]);
    }

    /**
     * @param array<mixed>|string $message
     */
    public static function recordDatasetOutcome(
        int $teamId,
        int $federationId,
        string $pid,
        string $jobUuid,
        array|string $message,
        FederationJobRunStatus $status,
        int $attempts,
    ): self {
        return static::create([
            'team_id' => $teamId,
            'federation_id' => $federationId,
            'pid' => $pid,
            'job_uuid' => $jobUuid,
            'status' => $status,
            'details' => ['message' => $message],
            'job_attempts' => $attempts,
        ]);
    }

    public static function runRowForExecution(int $federationId, string $jobUuid): ?self
    {
        return static::where('federation_id', $federationId)
            ->where('job_uuid', $jobUuid)
            ->whereNull('pid')
            ->first();
    }

    public static function closeRun(int $federationId, string $jobUuid, FederationJobRunStatus $status, ?string $message = null): void
    {
        $run = static::runRowForExecution($federationId, $jobUuid);

        if (!$run) {
            return;
        }

        $run->status = $status;

        if ($message !== null) {
            $run->details = ['message' => $message];
        }

        $run->save();
    }

    /**
     * Latest recorded row per dataset (pid) for one federation execution.
     *
     * @return Collection<int, FederationJobRun>
     */
    public static function latestPerPidForExecution(int $federationId, string $jobUuid): Collection
    {
        return static::datasetRows()
            ->where('federation_id', $federationId)
            ->where('job_uuid', $jobUuid)
            ->whereIn('id', function ($query) use ($federationId, $jobUuid) {
                $query->selectRaw('MAX(id)')
                    ->from((new self())->getTable())
                    ->where('federation_id', $federationId)
                    ->where('job_uuid', $jobUuid)
                    ->whereNotNull('pid')
                    ->groupBy('pid');
            })
            ->get();
    }

    /**
     * @param Builder<FederationJobRun> $query
     * @return Builder<FederationJobRun>
     */
    public function scopeDatasetRows(Builder $query): Builder
    {
        return $query->whereNotNull('pid');
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
            ->selectRaw('MAX(updated_at) as finished_at')
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
                // traser returns {message, details} on failure, older rows may hold a plain string.
                $traserMessage = is_array($raw['traser_message'])
                    ? ($raw['traser_message']['message'] ?? null)
                    : $raw['traser_message'];

                if (is_string($traserMessage) && $traserMessage !== '') {
                    return [['schema' => null, 'message' => $traserMessage]];
                }
            }
        }

        return [['schema' => null, 'message' => 'An error occurred while processing this dataset.']];
    }
}
