<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class FederationJobRun extends Model
{
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

    /** Latest recorded row per dataset (pid) for one federation execution. */
    public static function latestPerPidForExecution(int $federationId, string $jobUuid): Collection
    {
        $pids = static::where('federation_id', $federationId)
            ->where('job_uuid', $jobUuid)
            ->select('pid')->distinct()->pluck('pid');

        return $pids->map(fn (string $pid) => static::where('federation_id', $federationId)
            ->where('job_uuid', $jobUuid)
            ->where('pid', $pid)
            ->latest()
            ->first());
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
