<?php

namespace App\Behat\Context;

use Exception;
use Faker\Factory as Faker;
use PHPUnit\Framework\Assert;
use Behat\Behat\Context\Context;
use App\Models\Application;
use App\Models\Dataset;
use Illuminate\Support\Facades\Http;

/**
 * Defines application create features from the specific context.
 */
class ManageDatasetWithApplicationContext implements Context
{
    private $baseUri;
    private $accessToken;
    private $faker;
    private $response;
    private $application;
    private $datasetId;

    /**
     * Initializes context.
     */
    public function __construct()
    {
        $this->baseUri = config('app.url');
        $this->faker = Faker::create();
        $this->application = SharedContext::get('application');
    }

    /**
     * @Given I have valid application credentials
     */
    public function iHaveValidApplicationCredentials()
    {
        $application = Application::where([
            'app_id' => $this->application['app_id'],
            'client_id' => $this->application['client_id'],
        ])->first();

        if (!$application) {
            throw new Exception("The application was not found in the database.");
        }
    }

    /**
     * @When I post a new dataset with application credentials
     */
    public function iPostANewDatasetWithApplicationCredentials()
    {
        try {
            $payload = [
                "metadata" => $this->getMetadata(),
            ];

            $url = $this->baseUri . '/api/v1/integrations/datasets';

            $this->response = Http::withHeaders([
                'x-application-id' => $this->application['app_id'],
                'x-client-id' => $this->application['client_id'],
            ])->post($url, $payload);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @Then I should receive a successful response for create dataset with application with status code :statusCode
     */
    public function iShouldReceiveASuccessfulResponseForCreateDatasetWithApplicationWithStatusCode($statusCode)
    {
        Assert::assertEquals(
            $statusCode,
            $this->response->getStatusCode(),
            "Expected status code {$statusCode}, and received {$this->response->getStatusCode()}."
        );
    }

    /**
     * @Then the new dataset should exist in the database created through the application
     */
    public function theNewDatasetShouldExistInTheDatabaseCreatedThroughTheApplication()
    {
        $responseData = json_decode($this->response->body(), true);

        if (!isset($responseData['data'])) {
            throw new Exception("The response does not contain the expected key.");
        }
        $this->datasetId = (int) $responseData['data'];

        $dataset = Dataset::where([
            'id' => $this->datasetId,
        ])->first();

        if (!$dataset) {
            throw new Exception("The dataset was not found in the database.");
        }
    }

    /**
     * @When I delete the dataset
     */
    public function iDeleteTheDataset()
    {
        try {
            $url = $this->baseUri . '/api/v1/integrations/datasets/' . $this->datasetId;

            $this->response = Http::withHeaders([
                'x-application-id' => $this->application['app_id'],
                'x-client-id' => $this->application['client_id'],
            ])->delete($url, []);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @Then the dataset should not exist in the database
     */
    public function theDatasetShouldNotExistInTheDatabase()
    {
        $dataset = Dataset::where([
            'id' => $this->datasetId,
        ])->first();

        if ($dataset) {
            throw new Exception("The dataset was found in the database.");
        }
    }

    public function getMetadata()
    {
        return [
            "metadata" => [
               "identifier" => htmlentities($this->faker->imageUrl(640, 480, 'animals', true), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
               "version" => "0.0.1",
               "issued" => "2020-08-10T00:00:00.000Z",
               "modified" => "2020-08-10T00:00:00.000Z",
               "revisions" => [],
               "summary" => [
                   "title" => $this->faker->words(10, true),
                   "abstract" => $this->faker->text(),
                   "publisher" => [
                       "identifier" => "https://web.www.healthdatagateway.org/5f86cd34980f41c6f02261f4",
                       "name" => "NHS DIGITAL",
                       "logo" => "",
                       "description" => "",
                       "contactPoint" => null,
                       "memberOf" => "ALLIANCE"
                   ],
                   "contactPoint" => "enquiries@nhsdigital.nhs.uk",
                   "keywords" => null,
                   "alternateIdentifiers" => null,
                   "doiName" => ""
               ],
               "documentation" => [
                   "description" => $this->faker->text(),
                   "associatedMedia" => null,
                   "isPartOf" => null,
               ],
               "coverage" => [
                   "spatial" => "United Kingdom,England",
                   "typicalAgeRange" => "",
                   "physicalSampleAvailability" => null,
                   "followup" => "",
                   "pathway" => "",
               ],
               "provenance" => [
                   "origin" => [
                       "purpose" => null,
                       "source" => null,
                       "collectionSituation" => null,
                   ],
                   "temporal" => [
                       "accrualPeriodicity" => "MONTHLY",
                       "distributionReleaseDate" => null,
                       "startDate" => "2011-01-04",
                       "endDate" => "2013-03-31",
                       "timeLag" => null,
                   ]
               ],
               "accessibility" => [
                   "usage" => [
                       "dataUseLimitation" => null,
                       "dataUseRequirements" => null,
                       "resourceCreator" => null,
                       "investigations" => null,
                       "isReferencedBy" => null,
                   ],
                   "access" => [
                       "accessRights" => "https://digital.nhs.uk/binaries/content/assets/website-assets/services/dars/nhs_digital_approved_edition_2_dsa_demo.pdf",
                       "accessService" => null,
                       "accessRequestCost" => null,
                       "deliveryLeadTime" => null,
                       "jurisdiction" => "GB-ENG",
                       "dataProcessor" => null,
                       "dataController" => null,
                   ],
                   "formatAndStandards" => [
                       "vocabularyEncodingScheme" => null,
                       "conformsTo" => null,
                       "language" => null,
                       "format" => null,
                   ]
               ],
               "enrichmentAndLinkage" => [
                   "qualifiedRelation" => null,
                   "derivation" => null,
                   "tools" => null,
               ],
               "observations" => [],
               "structuralMetadata" => [],
           ],
       ];
    }
}
