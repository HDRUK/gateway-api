<?php

namespace App\Console\Commands;

use App\Models\Dur;
use App\Models\DatasetVersion;
use Illuminate\Console\Command;
use App\Models\DurHasDatasetVersion;

class UpdateDurHasDatasetsGat6531 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-dur-has-datasets-gat6531';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Data Use Register not matching Gateway Datasets & BioSamples during upload process';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $durs = Dur::select('id', 'non_gateway_datasets')->get();

        foreach ($durs as $dur) {
            $nonGatewayDatasets = array_filter(array_map('trim', $dur['non_gateway_datasets']));
            $this->info("non_gateway_datasets before :: " . json_encode($nonGatewayDatasets));
            $durId = $dur->id;
            $unmatched = [];
            foreach ($nonGatewayDatasets as $nonGatewayDataset) {
                $datasetName = trim($nonGatewayDataset);
                $search = DatasetVersion::whereRaw(
                    'LOWER(short_title) LIKE ?',
                    ['%' . strtolower($datasetName) . '%']
                )->latest('version')->first();

                if (is_null($search)) {
                    $this->warn("non_gateway_datasets not-found :: " . $datasetName);
                    $unmatched[] = $datasetName;
                    continue;
                }

                $this->info("non_gateway_datasets found :: " . $datasetName);
                $datasetVersionId = $search->id;
                DurHasDatasetVersion::updateOrCreate([
                    'dur_id' => $durId,
                    'dataset_version_id' => $datasetVersionId,
                ], [
                    'dur_id' => $durId,
                    'dataset_version_id' => $datasetVersionId,
                ]);
            }

            $this->info("non_gateway_datasets after :: " . json_encode($unmatched));
            Dur::where('id', $durId)->update([
                'non_gateway_datasets' => $unmatched
            ]);
            $this->info('Dur Id :: ' . $durId . ' done');
        }
    }
}
