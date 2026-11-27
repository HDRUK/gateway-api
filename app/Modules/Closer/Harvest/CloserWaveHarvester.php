<?php

namespace App\Modules\Closer\Harvest;

use App\Modules\Closer\CloserClient;
use App\Modules\Closer\Models\CloserDataset;
use App\Modules\Closer\Models\CloserQuestion;
use App\Modules\Closer\Models\CloserVariable;
use App\Modules\Closer\Models\CloserWave;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Copies one CLOSER wave (StudyUnit) into closer_waves, closer_datasets,
 * closer_variables and closer_questions:
 *
 *   1. read the wave; stop if it is already complete at this version,
 *   2. follow its references, fetching each level in batches via _getList:
 *      wave -> datasets -> their statistics -> the variables those describe
 *      -> the questions those variables come from (label and text only),
 *   3. replace the wave's rows in one transaction.
 *
 * Following references avoids the wave's typed set, which lists every item
 * (~45k for a large wave, ~50MB once decoded) when we need about a third.
 * The cost: a variable no dataset has statistics for is not harvested
 * (9 of 8,329 in the ALSPAC pregnancy wave).
 *
 * A failure leaves the previous rows and version in place and marks the wave
 * failed, so the next run tries again.
 */
class CloserWaveHarvester
{
    private const INSERT_CHUNK = 500;

    public function __construct(
        private readonly CloserClient $closer,
        private readonly DdiItemParser $parser,
    ) {
    }

    public function harvest(string $agency, string $identifier, bool $force = false): CloserWave
    {
        $item = $this->closer->get("item/{$agency}/{$identifier}");
        if (! $item->successful()) {
            throw new RuntimeException("CLOSER wave request failed: HTTP {$item->status()}.");
        }

        $version = (int) $item->json('Version');
        $wave = CloserWave::firstOrCreate(['agency' => $agency, 'identifier' => $identifier]);

        if (! $force && $wave->status === CloserWave::STATUS_COMPLETE && $wave->version === $version) {
            return $wave;
        }

        $wave->update(['status' => CloserWave::STATUS_HARVESTING, 'error' => null]);

        try {
            $studyUnit = $this->parser->studyUnit((string) $item->json('Item'));

            $datasets = $this->fetch($studyUnit['physical_instances'], $this->parser->physicalInstance(...));
            $statistics = $this->fetch(
                $this->unique(array_merge(...array_column($datasets, 'statistics'))),
                $this->parser->variableStatistics(...),
            );
            $variables = $this->fetch(
                $this->unique(array_filter(array_column($statistics, 'variable'))),
                $this->parser->variable(...),
            );
            $questions = $this->fetch(
                $this->unique(array_filter(array_column($variables, 'question'))),
                $this->parser->question(...),
            );

            $this->replace($wave, $datasets, $variables, $statistics, $questions);

            $wave->update([
                'version' => $version,
                'title' => $studyUnit['title'],
                'status' => CloserWave::STATUS_COMPLETE,
                'dataset_count' => count($datasets),
                'variable_count' => count($variables),
                'harvested_at' => now(),
            ]);
        } catch (Throwable $e) {
            $wave->update([
                'status' => CloserWave::STATUS_FAILED,
                'error' => class_basename($e).': '.Str::limit($e->getMessage(), 500),
            ]);

            throw $e;
        }

        return $wave->refresh();
    }

    /**
     * Drop repeated references (a statistic can be listed by more than one
     * dataset).
     *
     * @return list<array{AgencyId: string, Identifier: string, Version: int}>
     */
    private function unique(array $references): array
    {
        $unique = [];
        foreach ($references as $reference) {
            $unique[$reference['AgencyId'].'/'.$reference['Identifier'].'/'.$reference['Version']] = $reference;
        }

        return array_values($unique);
    }

    /**
     * Fetch items in batches and parse each batch as it arrives, so only the
     * parsed fields are held in memory, never a whole wave of XML.
     *
     * @template T
     *
     * @param  callable(string): T  $parse
     * @return list<T>
     */
    private function fetch(array $identifiers, callable $parse): array
    {
        $parsed = [];

        foreach (array_chunk($identifiers, max(1, (int) config('services.closer.batch_size', 200))) as $batch) {
            $response = $this->closer->post('item/_getList', ['Identifiers' => $batch]);
            if (! $response->successful()) {
                throw new RuntimeException("CLOSER _getList request failed: HTTP {$response->status()}.");
            }

            foreach ($response->json() ?? [] as $item) {
                $parsed[] = $parse((string) ($item['Item'] ?? ''));
            }
            unset($response);
        }

        return $parsed;
    }

    private function replace(CloserWave $wave, array $datasets, array $variables, array $statistics, array $questions): void
    {
        $statisticsByVariable = [];
        foreach ($statistics as $statistic) {
            if ($statistic['variable'] !== null) {
                $statisticsByVariable[$statistic['variable']['Identifier']] = $statistic;
            }
        }

        DB::transaction(function () use ($wave, $datasets, $variables, $statisticsByVariable, $questions) {
            CloserVariable::where('closer_wave_id', $wave->id)->delete();
            CloserQuestion::where('closer_wave_id', $wave->id)->delete();
            CloserDataset::where('closer_wave_id', $wave->id)->delete();

            $now = now();

            foreach (array_chunk($questions, self::INSERT_CHUNK) as $chunk) {
                CloserQuestion::insert(array_map(fn (array $question) => [
                    'closer_wave_id' => $wave->id,
                    'identifier' => $question['identifier'],
                    'version' => $question['version'],
                    'type' => $question['type'],
                    'label' => $question['label'],
                    'text' => $question['text'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $chunk));
            }
            $questionIds = CloserQuestion::where('closer_wave_id', $wave->id)->pluck('id', 'identifier')->all();

            $datasetIdByStatistic = [];

            foreach ($datasets as $dataset) {
                $row = CloserDataset::create([
                    'closer_wave_id' => $wave->id,
                    'identifier' => $dataset['identifier'],
                    'version' => $dataset['version'],
                    'title' => $dataset['title'],
                    'file_name' => $dataset['file_name'],
                    'case_quantity' => $dataset['case_quantity'],
                ]);

                foreach ($dataset['statistics'] as $statistic) {
                    $datasetIdByStatistic[$statistic['Identifier']] = $row->id;
                }
            }

            $rows = array_map(function (array $variable) use ($wave, $statisticsByVariable, $datasetIdByStatistic, $questionIds, $now) {
                $statistic = $statisticsByVariable[$variable['identifier']] ?? null;

                return [
                    'closer_wave_id' => $wave->id,
                    'closer_dataset_id' => $statistic ? ($datasetIdByStatistic[$statistic['identifier']] ?? null) : null,
                    'closer_question_id' => $variable['question'] ? ($questionIds[$variable['question']['Identifier']] ?? null) : null,
                    'identifier' => $variable['identifier'],
                    'version' => $variable['version'],
                    'name' => $variable['name'],
                    'label' => $variable['label'],
                    'question_identifier' => $variable['question']['Identifier'] ?? null,
                    'data_type' => $variable['data_type'],
                    // Bulk insert skips casts, so encode here.
                    'summary_statistics' => $statistic ? json_encode($statistic['summary']) : null,
                    'frequencies' => $statistic ? json_encode($statistic['frequencies']) : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }, $variables);

            foreach (array_chunk($rows, self::INSERT_CHUNK) as $chunk) {
                CloserVariable::insert($chunk);
            }
        });
    }
}
