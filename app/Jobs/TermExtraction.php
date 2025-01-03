<?php

namespace App\Jobs;

use Auditor;
use Exception;

use App\Models\Dataset;
use App\Models\NamedEntities;
use App\Models\DatasetVersionHasNamedEntities;

use App\Http\Traits\IndexElastic;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Illuminate\Support\Facades\Http;

class TermExtraction implements ShouldQueue
{
    use IndexElastic;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries;
    public $timeout;

    protected string $tedUrl = '';
    protected string $datasetId = '';
    protected string $datasetVersionId = '';
    protected int $version = 0;
    protected string $data = '';
    protected bool $usePartialExtraction = true;

    protected bool $reIndexElastic = true;

    /**
     * Create a new job instance.
     */
    public function __construct(string $datasetId, string $datasetVersionId, int $version, string $data, ?bool $elasticIndex = true, ?bool $usePartialExtraction = true)
    {
        $this->timeout = config('jobs.default_timeout', 600);
        $this->tries = config('jobs.ntries', 2);
        $this->usePartialExtraction = is_null($usePartialExtraction) ? config('ted.use_partial', true) : $usePartialExtraction;
        $this->datasetId = $datasetId;
        $this->datasetVersionId = $datasetVersionId;
        $this->version = $version;
        $this->data = $data;
        $this->tedUrl = config('ted.url');
        $this->reIndexElastic = is_null($elasticIndex) ? true : $elasticIndex;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        Auditor::log([
            'action_type' => 'DEBUG',
            'action_name' => class_basename($this) . '@' . __FUNCTION__,
            'ted.url' => config('ted.url'),
            'ted.use_partial' => config('ted.use_partial'),
            'jobs.default_timeout' => config('jobs.default_timeout'),
            'jobs.ntries' => config('jobs.ntries'),
        ]);

        $data = json_decode(gzdecode(gzuncompress(base64_decode($this->data))), true);
        if($this->usePartialExtraction) {
            //data is partial - summary data only
            $this->postSummaryToTermExtractionDirector(json_encode($data));
        } else {
            $this->postToTermExtractionDirector(json_encode($data));
        }

        if ($this->reIndexElastic) {
            $this->reindexElastic($this->datasetId);
        }
    }

    /**
     * Passes the incoming metadata summary to TED for extraction
     *
     * @param string $summary   The summary json passed to this process
     *
     * @return void
     */
    private function postSummaryToTermExtractionDirector(string $summary): void
    {
        try {

            $response = Http::timeout($this->timeout * 2)->withBody(
                $summary,
                'application/json'
            )->post($this->tedUrl . '/summary');

            if ($response->successful() && array_key_exists('extracted_terms', $response->json())) {
                foreach ($response->json()['extracted_terms'] as $term) {
                    // Check if the named entity already exists
                    $namedEntity = NamedEntities::firstOrCreate(['name' => $term]);

                    DatasetVersionHasNamedEntities::withTrashed()->updateOrCreate([
                        'dataset_version_id' => $this->datasetVersionId,
                        'named_entities_id' => $namedEntity->id
                    ]);
                }
            }

        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
    * Passes the incoming dataset to TED for extraction
    *
    * @param string $dataset   The dataset json passed to this process
    *
    * @return void
    */
    private function postToTermExtractionDirector(string $dataset): void
    {
        try {
            $response = Http::timeout($this->timeout * 2)->withBody(
                $dataset,
                'application/json'
            )->post($this->tedUrl  . '/datasets');

            if ($response->successful() && array_key_exists('extracted_terms', $response->json())) {
                foreach ($response->json()['extracted_terms'] as $term) {
                    // Check if the named entity already exists
                    $namedEntity = NamedEntities::firstOrCreate(['name' => $term]);

                    DatasetVersionHasNamedEntities::withTrashed()->updateOrCreate([
                        'dataset_version_id' => $this->datasetVersionId,
                        'named_entities_id' => $namedEntity->id
                    ]);
                }
            }

        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    public function tags(): array
    {
        return [
            'term_extraction',
            'dataset_id:' . $this->datasetId,
            'dataset_version_id:' . $this->datasetVersionId,
        ];
    }
}
