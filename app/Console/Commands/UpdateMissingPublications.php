<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Dataset;
use App\Models\Publication;
use Illuminate\Console\Command;
use App\Http\Traits\IndexElastic;
use Illuminate\Support\Facades\Http;
use App\Models\PublicationHasDatasetVersion;

class UpdateMissingPublications extends Command
{
    use IndexElastic;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-missing-publications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command import missing publications';


    private $csvData = [];

    public function __construct()
    {
        parent::__construct();
        $this->readMigrationFile(storage_path() . '/migration_files/finding.publications.update.csv');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $progressbar = $this->output->createProgressBar(count($this->csvData));
        $progressbar->start();

        foreach ($this->csvData as $item) {
            $publicationMongoId = trim($item['Mongo Id']);
            $publicationDoi = 'https://doi.org/' . trim($item['Possible DOI']);
            $publicationDatasetLinks = trim($item['Dataset Links pid']);
            $publicationUploader = trim($item['Uploader']);

            if (!$publicationDoi) {
                $progressbar->advance();
                continue;
            }

            $checkDoiInPub = Publication::where('paper_doi', $publicationDoi)->first();
            if (!is_null($checkDoiInPub)) {
                $progressbar->advance();
                continue;
            }

            $url = env('SEARCH_SERVICE_URL', 'http://localhost:8003') . '/search/federated_papers/doi';
            $response = Http::post($url, [
                'query' => $publicationDoi,
            ]);

            $return = $response->json();

            if (!isset($return['resultList']['result']) || !is_array($return['resultList']['result'])) {
                $progressbar->advance();
                continue;
            }

            if (count($return['resultList']['result']) !== 1) {
                $progressbar->advance();
                continue;
            }

            $publication = [];
            $this->info($publicationDoi);
            $returnShort = $return['resultList']['result'][0];
            $publication['paper_title'] = $returnShort['title'];
            $publication['authors'] = $returnShort['authorString'];
            $publication['abstract'] = $returnShort['abstractText'];
            $publication['is_preprint'] = str_contains($returnShort['id'], 'PPR') ? true : false;
            if (!$publication['is_preprint']) {
                $publication['journal_name'] = $returnShort['journalInfo']['journal']['title'] ?? '';
                $publication['year_of_publication'] = $returnShort['pubYear'];
            } else {
                $publication['journal_name'] = '';
                $publication['year_of_publication'] = '';
            }
            $publication['paper_doi'] = $publicationDoi;
            $publication['mongo_id'] = $publicationMongoId;
            $publication['status'] = 'ACTIVE';
            $publication['publication_type'] = '';
            $publication['publication_type_mk1'] = '';

            // user/uploader
            $user = User::where('mongo_id', $publicationUploader)->first();
            if (!is_null($user)) {
                $publication['owner_id'] = $user->id;
            }

            $publication['url'] = $this->getUrl($returnShort);

            $pub = Publication::create($publication);
            $pubId = (int)$pub->id;

            // datasets
            $cleanDataLinkIds = str_replace(["[", "]", "'"], "", $publicationDatasetLinks);
            $pubDataLinkIds = explode(", ", $cleanDataLinkIds);
            foreach ($pubDataLinkIds as $pubDataLinkId) {
                $dataset = Dataset::where('mongo_pid', trim($pubDataLinkId))->select('id')->first();
                if (is_null($dataset)) {
                    continue;
                }

                $datasetVersionId = Dataset::where('id', $dataset->id)->first()->latestVersion()->id;
                if (!is_null($datasetVersionId)) {
                    $this->info($datasetVersionId . PHP_EOL);

                    $arrCreate = [
                        'publication_id' => $pubId,
                        'dataset_version_id' => $datasetVersionId,
                        'link_type' => 'USING', // Assuming default link_type is 'USING'
                    ];

                    PublicationHasDatasetVersion::create($arrCreate);

                    $this->reindexElastic($dataset['id']);
                }
            }

            $progressbar->advance();
        }
    }

    private function getUrl(array $input)
    {
        if (!array_key_exists('fullTextUrlList', $input)) {
            return null;
        }

        foreach ($input['fullTextUrlList']['fullTextUrl'] as $item) {
            if ($item['documentStyle'] === 'html') {
                return $item['url'];
            }
        }
    }

    private function readMigrationFile(string $migrationFile): void
    {
        $file = fopen($migrationFile, 'r');
        $headers = fgetcsv($file);

        while (($row = fgetcsv($file)) !== false) {
            $item = [];
            foreach ($row as $key => $value) {
                $item[trim($headers[$key], "\xEF\xBB\xBF")] = $value ?: '';
            }

            $this->csvData[] = $item;
        }

        fclose($file);
    }
}
