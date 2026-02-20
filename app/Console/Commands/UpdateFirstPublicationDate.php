<?php

namespace App\Console\Commands;

use App\Models\Publication;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class UpdateFirstPublicationDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-first-publication-date';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $urlBasePmc = "https://www.ebi.ac.uk/europepmc/webservices/rest/search?query=";

        $publications = Publication::whereNotNull('paper_doi')->whereNull('first_publication_date')->select(['id', 'paper_doi'])->get();

        foreach ($publications as $publication) {
            $publictionId = $publication->id;
            $paperCode = str_replace('https://doi.org/', '', $publication->paper_doi);
            $url = $urlBasePmc . $paperCode . "&format=json";

            try {
                $response = Http::retry(3, 100)->get($url);
            } catch (\Exception $e) {
                $this->warn("Skipping publication {$publictionId}: " . $e->getMessage());
                continue;
            }

            $responseBody = $response->json();
            $firstPublicationDate = extractValueFromPath($responseBody, 'resultList/result/0/firstPublicationDate');

            if ($firstPublicationDate) {
                $publication->first_publication_date = $firstPublicationDate;
                $publication->save();
                $this->info('publication ' . $publictionId . ' :: firstPublicationDate :: ' . $firstPublicationDate);
            } else {
                $this->warn("Skipping publication {$publictionId}: data not found.");
            }
        }
    }
}
