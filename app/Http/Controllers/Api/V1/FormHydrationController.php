<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use MetadataManagementController as MMC;
use App\Http\Controllers\Controller;
use App\Models\Dataset;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class FormHydrationController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/v1/form_hydration/schema",
     *      operationId="getFormSchema",
     *      tags={"Form Hydration"},
     *      summary="Retrieve form schema data",
     *      description="Retrieves form schema. Model and version are set at app level: send header X-CRUK-Session for CRUK schema, otherwise default schema is used.",
     *      @OA\Parameter(
     *          name="X-CRUK-Session",
     *          in="header",
     *          required=false,
     *          description="When present, form hydration uses the CRUK schema (model/version).",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="object"
     *          )
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad request. Missing required parameters or invalid parameters."
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Internal server error. Failed to retrieve form schema data."
     *      )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $model = Config::get('form_hydration.schema.model');
        $version = Config::get('form_hydration.schema.latest_version');

        $url = sprintf(Config::get('form_hydration.schema.url'), $model, $version);

        $response = Http::get($url);
        if ($response->successful()) {
            $payload = $response->json();
            return response()->json(['data' => $payload]);
        } else {
            return response()->json([
               'message' => 'Failed to retrieve form hydration from ' . $url,
            ], Config::get('statuscodes.STATUS_BAD_REQUEST.code'));
        }

    }

    /**
     * @OA\Get(
     *      path="/api/v1/form_hydration",
     *      operationId="onboardingFormHydration",
     *      tags={"Form Hydration"},
     *      summary="Retrieve form schema data",
     *      description="Retrieves onboarding form schema. Model and version are set at app level: send header X-CRUK-Session for CRUK schema, otherwise default schema is used.",
     *      @OA\Parameter(
     *          name="X-CRUK-Session",
     *          in="header",
     *          required=false,
     *          description="When present, form hydration uses the CRUK schema (model/version).",
     *          @OA\Schema(type="string")
     *      ),
     *       @OA\Parameter(
     *          name="dataTypes",
     *          in="query",
     *          required=false,
     *          description="The data types of the dataset about to be onboarded.",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="object"
     *          )
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad request. Missing required parameters or invalid parameters."
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Internal server error. Failed to retrieve form schema data."
     *      )
     * )
     */
    public function onboardingFormHydration(Request $request): JsonResponse
    {
        $model = Config::get('form_hydration.schema.model');
        $version = Config::get('form_hydration.schema.latest_version');
        $dataTypes = $request->input('dataTypes', '');
        $dataTypes = ($dataTypes === 'undefined' || $dataTypes === 'null') ? '' : $dataTypes;
        $teamId = $request->input('team_id', null);

        $hydrationJson = MMC::getOnboardingFormHydrated($model, $version, $dataTypes);

        if ($teamId) {
            $hydrationJson['defaultValues'] = $this->getDefaultValues((int)$teamId);
        } else {
            $hydrationJson['defaultValues'] = $this->generalDefaults();
        }

        return response()->json([
            'message' => 'success',
            'data' => $hydrationJson,
        ]);
    }

    private function getDefaultValues(int $id): array
    {
        $team = Team::findOrFail($id);

        $defaultValues = array();
        $defaultValues['identifier'] = $team['id'];
        $defaultValues['Name of Data Custodian'] = $team['name'];
        $defaultValues['Organisation Logo'] = (is_null($team['team_logo']) || strlen(trim($team['team_logo'])) === 0) ? null : (preg_match('/^https?:\/\//', $team['team_logo']) ? $team['team_logo'] : Config::get('services.media.base_url') . $team['team_logo']);
        $defaultValues['Organisation Description'] = $team['name'];
        $defaultValues['contact point'] = $team['contact_point']; //warning - this is `summary.dataCustodian.contact_point`, not `summary.contact_point` which is called "Contact point"
        $defaultValues['Organisation Membership'] = $team['memberOf'];
        $defaultValues['Data Controller'] = $team['name'];
        $defaultValues['Data Processor'] = $team['name'];

        $datasets = Dataset::where('team_id', $id)->get();
        $datasetDefaultValues = array();
        if (count($datasets) > 0) { //protect if team has no datasets

            foreach ($datasets as $dataset) {
                $dataset['metadata'] = $dataset->latestVersion()->metadata;
            }

            $datasets = $datasets->toArray();

            $datasetDefaultValues['Data use limitation'] = $this->mostCommonValue(
                'metadata.metadata.accessibility.usage.dataUseLimitation',
                $datasets,
                true
            );
            $datasetDefaultValues['Data use requirements'] = $this->mostCommonValue(
                'metadata.metadata.accessibility.usage.dataUseRequirements',
                $datasets,
                true
            );
            $datasetDefaultValues['Access rights'] = $this->mostCommonValue(
                'metadata.metadata.accessibility.access.accessRights',
                $datasets
            );
            $datasetDefaultValues['Access service description'] = $this->mostCommonValue(
                'metadata.metadata.accessibility.access.accessService',
                $datasets
            );
            $datasetDefaultValues['Access request cost'] = $this->mostCommonValue(
                'metadata.metadata.accessibility.access.accessRequestCost',
                $datasets
            );
            $datasetDefaultValues['Time to dataset access'] = $this->mostCommonValue(
                'metadata.metadata.accessibility.access.deliveryLeadTime',
                $datasets
            );
            $datasetDefaultValues['Format'] = $this->mostCommonValue(
                'metadata.metadata.accessibility.formatAndStandards.formats',
                $datasets,
                true
            );
        }

        $defaultValues = array_merge($defaultValues, $datasetDefaultValues, $this->generalDefaults());
        return $defaultValues;
    }

    private function generalDefaults(): array
    {
        return [
            'Jurisdiction' => ['UK'],
            'Language' => ['en'],
            'Biological sample availability' => ['None/not available']
        ];
    }

    private function mostCommonValue(string $path, array $datasets, bool $isArray = false): mixed
    {
        $values = array();
        foreach ($datasets as $dataset) {
            $v = $this->getValueFromPath($dataset, $path);
            $values[] = is_scalar($v) ? (string)$v : '';
        }

        $countMap = array_count_values($values);
        arsort($countMap);
        $mostCommon = array_keys($countMap)[0];

        if ($isArray) {
            return $mostCommon === '' ? [] : explode(';,;', $mostCommon);
        } else {
            return $mostCommon === '' ? null : $mostCommon;
        }
    }

    public function getValueFromPath(array $item, string $path): mixed
    {
        $keys = explode('.', $path);

        $return = $item;
        foreach ($keys as $key) {
            if (isset($return[$key])) {
                $return = $return[$key];
            } else {
                return null;
            }
        }

        return $return;
    }
}
