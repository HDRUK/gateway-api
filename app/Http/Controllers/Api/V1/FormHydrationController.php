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
     *      description="Retrieves form schema data based on the provided model and version.",
     *      @OA\Parameter(
     *          name="model",
     *          in="query",
     *          required=false,
     *          description="The model for which form schema is requested.",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="version",
     *          in="query",
     *          required=false,
     *          description="The version of the model for which form schema is requested.",
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
    public function index(Request $request): JsonResponse
    {
        $model = $request->input('model',Config::get('form_hydration.schema.model'));
        $version = $request->input('version',Config::get('form_hydration.schema.latest_version'));

        $url = sprintf(Config::get('form_hydration.schema.url'), $model, $version);

        $response = Http::get($url);
        if ($response->successful()) {
            $payload = $response->json(); 
            return response()->json(["data"=>$payload]);
        } else {
             return response()->json([
                'message' => "Failed to retrieve form hydration from ".$url,
            ], Config::get('statuscodes.STATUS_BAD_REQUEST.code'));
        }

    }

    /**
     * @OA\Get(
     *      path="/api/v1/form_hydration",
     *      operationId="onboardingFormHydration",
     *      tags={"Form Hydration"},
     *      summary="Retrieve form schema data",
     *      description="Retrieves form schema data based on the provided model and version.",
     *      @OA\Parameter(
     *          name="name",
     *          in="query",
     *          required=false,
     *          description="The model name for which form schema is requested.",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="version",
     *          in="query",
     *          required=false,
     *          description="The version of the model for which form schema is requested.",
     *          @OA\Schema(
     *              type="string"
     *          )
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
        $model = $request->input('model', Config::get('form_hydration.schema.model'));
        $version = $request->input('version', Config::get('form_hydration.schema.latest_version'));
        $dataTypes = $request->input('dataTypes', '');
        $team = $request->input('team_id', null);

        $hydrationJson = MMC::getOnboardingFormHydrated($model, $version, $dataTypes);
        if ($team) {
            $hydrationJson['defaultValues'] = $this->getDefaultValues((int) $team);
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
        $datasets = Dataset::where('team_id', $id)->get();
        foreach ($datasets as $dataset) {
            $version = $dataset->latestVersion();
            $dataset['metadata'] = $version->metadata;
        }
        $datasets = $datasets->toArray();
        $defaultValues = array();
        $defaultValues['identifier'] = $team['id'];
        $defaultValues['Name of data provider'] = $team['name'];
        $defaultValues['Organisation Logo'] = $team['team_logo'];
        $defaultValues['Organisation Description'] = $team['name'];
        $defaultValues['contact point'] = $team['contact_point'];
        $defaultValues['Organisation Membership'] = $team['memberOf'];
        $defaultValues['Data Controller'] = $team['name'];
        $defaultValues['Data Processor'] = $team['name'];

        $defaultValues['Data use limitation'] = $this->mostCommonValue(
            'metadata.metadata.accessibility.usage.dataUseLimitation',
            $datasets
        );
        $defaultValues['Data use requirements'] = $this->mostCommonValue(
            'metadata.metadata.accessibility.usage.dataUseRequirements',
            $datasets
        );
        $defaultValues['Access rights'] = $this->mostCommonValue(
            'metadata.metadata.accessibility.access.accessRights',
            $datasets
        );
        $defaultValues['Access service description'] = $this->mostCommonValue(
            'metadata.metadata.accessibility.access.accessService',
            $datasets
        );
        $defaultValues['Access request cost'] = $this->mostCommonValue(
            'metadata.metadata.accessibility.access.accessRequestCost',
            $datasets
        );
        $defaultValues['Time to dataset access'] = $this->mostCommonValue(
            'metadata.metadata.accessibility.access.deliveryLeadTime',
            $datasets
        );
        $defaultValues['Format'] = $this->mostCommonValue(
            'metadata.metadata.accessibility.formatAndStandards.format',
            $datasets
        );

        $defaultValues = array_merge($defaultValues, $this->generalDefaults());
        return $defaultValues;
    }

    private function generalDefaults(): array
    {
        return [
            'Jurisdiction' => 'UK',
            'Language' => 'en',
            'Biological sample availability' => 'None/not Available'
        ];
    }

    private function mostCommonValue(string $path, array $datasets): string 
    {
        $values = array();
        foreach ($datasets as $dataset) {
            $v = $this->getValueFromPath($dataset, $path);
            $values[] = is_null($v)? "" : $this->getValueFromPath($dataset, $path);
        }
        $countMap = array_count_values($values);
        arsort($countMap);
        $mostCommon = array_keys($countMap)[0];
        return $mostCommon;
    }

    public function getValueFromPath(array $item, string $path) 
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
