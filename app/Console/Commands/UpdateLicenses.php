<?php

namespace App\Console\Commands;

use App\Models\License;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class UpdateLicenses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-eu-licenses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command update EU license in Gateway';

    protected $urlVocabularies = 'https://data.europa.eu/api/hub/search/vocabularies/licence';
    protected $urlLicense = 'http://publications.europa.eu/resource/authority/licence/';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $getVocabulariesLicense = $this->getVocabulariesLicense();
            $vocabulariesLicence = $getVocabulariesLicense['result']['results'];

            // EU
            foreach ($vocabulariesLicence as $license) {
                if (!array_key_exists('en', $license['pref_label'])) {
                    continue;
                }
                $label = $license['pref_label']['en'];

                $licenseId = (string) $license['id'];

                $data = $this->getLicenseDetails($licenseId); // parse resource

                if (!$data) {
                    continue;
                }

                $data['code'] = $licenseId;
                $data['label'] = $label;
                $data['verified'] = 1;
                $data['origin'] = 'EU';

                License::updateOrCreate(
                    [
                        'code' => $licenseId,
                        'origin' => 'EU',
                    ],
                    $data
                );
            }
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function getVocabulariesLicense()
    {
        try {
            return Http::timeout(5)->get($this->urlVocabularies);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function getLicenseDetails(string $code)
    {
        $url = $this->urlLicense . trim($code);
        try {
            $getLicense = Http::retry(3, 100)->timeout(5)->get($url);

            if ($getLicense->ok()) {
                return $this->convertXmlRdfToArray($getLicense->body());
            } else {
                return null;
            }
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function convertXmlRdfToArray(string $xmlRdf)
    {
        $xml = simplexml_load_string($xmlRdf);
        $xml->registerXPathNamespace('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
        $xml->registerXPathNamespace('skos', 'http://www.w3.org/2004/02/skos/core#');
        $xml->registerXPathNamespace('dcterms', 'http://purl.org/dc/terms/');
        $xml->registerXPathNamespace('owl', 'http://www.w3.org/2002/07/owl#');
        $xml->registerXPathNamespace('ns10', 'http://publications.europa.eu/ontology/authority/');

        $definitions = $xml->xpath('//skos:definition[@xml:lang="en"]');
        // $changeNotes = $xml->xpath('//skos:changeNote[@xml:lang="en"]');
        $startUses = $xml->xpath('//ns10:start.use');
        $endUses = $xml->xpath('//ns10:end.use');

        $data = [];
        foreach ($definitions as $index => $definition) {
            $data[] = [
                'definition' => (string) $definition,
                'valid_since' => $index < count($startUses) ? (string) $startUses[$index] : null,
                'valid_until' => $index < count($endUses) ? (string) $endUses[$index] : null,
            ];
        }

        return $data ? $data[0] : [];
    }
}
