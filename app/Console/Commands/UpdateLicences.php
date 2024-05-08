<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class UpdateLicences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-licences';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command integrate EU license list with Gateway';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $urlVocabulariesLicence = 'https://data.europa.eu/api/hub/search/vocabularies/licence';

        try {
            $getVocabulariesLicence = Http::timeout(5)->get($urlVocabulariesLicence);

            foreach ($getVocabulariesLicence['result']['results'] as $licence) {
                // dd($licence);
                if (!array_key_exists('en', $licence['pref_label'])) {
                    continue;
                }
                $label = $licence['pref_label']['en'];
                $resource = $licence['resource'];
                $licenceId = $licence['id'];
                print_r([
                    $label,
                    $resource,
                    $licenceId,
                ]);
                
                // exit();
            }

        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function getVocabulariesLicence()
    {

    }
}
