<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\NotFoundException;
use Config;
use Exception;
use App\Models\Dataset;
use App\Models\Dur;
use App\Models\Keyword;
use Illuminate\Http\Request;
use App\Models\DurHasDataset;
use App\Models\DurHasKeyword;
use App\Http\Requests\Dur\GetDur;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Dur\EditDur;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dur\CreateDur;
use App\Http\Requests\Dur\DeleteDur;
use App\Http\Requests\Dur\UpdateDur;
use App\Http\Traits\RequestTransformation;
use App\Models\Application;

use MetadataManagementController AS MMC;

class DurController extends Controller
{
    use RequestTransformation;

    /**
     * @OA\Get(
     *    path="/api/v1/dur",
     *    operationId="fetch_all_dur",
     *    tags={"Data Use Registers"},
     *    summary="DurController@index",
     *    description="Returns a list of dur",
     *    @OA\Response(
     *       response=200,
     *       description="Success",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string"),
     *          @OA\Property(property="data", type="array",
     *             @OA\Items(
     *                @OA\Property(property="id", type="integer", example="123"),
     *                @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                @OA\Property(property="non_gateway_datasets", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="non_gateway_applicants", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="funders_and_sponsors", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="other_approval_committees", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="gateway_outputs_tools", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="gateway_outputs_papers", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="non_gateway_outputs", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="project_title", type="string", example="Birth order and cord blood DNA methylation"),
     *                @OA\Property(property="project_id_text", type="string", example="B3649"),
     *                @OA\Property(property="organisation_name", type="string", example="LA-SER Europe Ltd"),
     *                @OA\Property(property="organisation_sector", type="string", example="Independent Sector Organisation"),
     *                @OA\Property(property="lay_summary", type="string", example="Non laudantium consequatur nulla minima. ..."),
     *                @OA\Property(property="technical_summary", type="string", example="Sint odit veritatis nam excepturi natus. ..."),
     *                @OA\Property(property="latest_approval_date", type="datetime", example="2023-04-03 12:00:00"),
     *                @OA\Property(property="manual_upload", type="boolean", example="0"),
     *                @OA\Property(property="rejection_reason", type="string", example=""),
     *                @OA\Property(property="sublicence_arrangements", type="string", example=""),
     *                @OA\Property(property="public_benefit_statement", type="string", example="Officiis provident sint iure. ..."),
     *                @OA\Property(property="data_sensitivity_level", type="string", example="Anonymous"),
     *                @OA\Property(property="project_start_date", type="datetime", example="2023-04-03 12:00:00"),
     *                @OA\Property(property="project_end_date", type="datetime", example="2023-04-03 12:00:00"),
     *                @OA\Property(property="access_date", type="datetime", example="2023-04-03 12:00:00"),
     *                @OA\Property(property="accredited_researcher_status", type="string", example="No"),
     *                @OA\Property(property="confidential_data_description", type="string", example=""),
     *                @OA\Property(property="dataset_linkage_description", type="string", example=""),
     *                @OA\Property(property="duty_of_confidentiality", type="string", example="Statutory exemption to flow confidential data without consent"),
     *                @OA\Property(property="legal_basis_for_data_article6", type="string", example="Ad labore atque asperiores eum quia. ..."),
     *                @OA\Property(property="legal_basis_for_data_article9", type="string", example="Quisquam illum ut porro quia. ..."),
     *                @OA\Property(property="national_data_optout", type="string", example="Not applicable"),
     *                @OA\Property(property="organisation_id", type="string", example="grid.10025.36"),
     *                @OA\Property(property="privacy_enhancements", type="string", example="Voluptatem veritatis dolorem amet culpa qui qui. ..."),
     *                @OA\Property(property="request_category_type", type="string", example="Health Services & Delivery"),
     *                @OA\Property(property="request_frequency", type="string", example="Public Health Research"),
     *                @OA\Property(property="access_type", type="string", example="Efficacy & Mechanism Evaluation"),
     *                @OA\Property(property="mongo_object_dar_id", type="string", example="MOBJIDDAR-2387"),
     *                @OA\Property(property="technicalSummary", type="string", example="Similique officia dolor nam. ..."),
     *                @OA\Property(property="enabled", type="boolean", example="1"),
     *                @OA\Property(property="last_activity", type="datetime", example="2023-04-03 12:00:00"),
     *                @OA\Property(property="counter", type="integer", example="34319"),
     *                @OA\Property(property="mongo_object_id", type="string", example="5f32a7d53b1d85c427e97c01"),
     *                @OA\Property(property="mongo_id", type="string", example="38873389090594430"),
     *                @OA\Property(property="datasets", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="keywords", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="users", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="user", type="array", example="{}", @OA\Items()),
     *                @OA\Property(property="team", type="array", example="{}", @OA\Items()),
     *                @OA\Property(property="applicant_id", type="string", example=""),
     *             ),
     *          ),
     *          @OA\Property(property="first_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/collections?page=1"),
     *          @OA\Property(property="from", type="integer", example="1"),
     *          @OA\Property(property="last_page", type="integer", example="1"),
     *          @OA\Property(property="last_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/collections?page=1"),
     *          @OA\Property(property="links", type="array", example="[]", @OA\Items(type="array", @OA\Items())),
     *          @OA\Property(property="next_page_url", type="string", example="null"),
     *          @OA\Property(property="path", type="string", example="http:\/\/localhost:8000\/api\/v1\/collections"),
     *          @OA\Property(property="per_page", type="integer", example="25"),
     *          @OA\Property(property="prev_page_url", type="string", example="null"),
     *          @OA\Property(property="to", type="integer", example="3"),
     *          @OA\Property(property="total", type="integer", example="3"),
     *       )
     *    )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = request('perPage', Config::get('constants.per_page'));
        $dur = Dur::where('enabled', 1)
            ->with([
                'datasets', 
                'keywords',
                'users' => function ($query) {
                    $query->distinct('id');
                },
                'applications' => function ($query) {
                    $query->distinct('id');
                },
                'user',
                'team',
                'application',
                ])->paginate($perPage);
        return response()->json(
            $dur
        );
    }

    /**
     * @OA\Get(
     *    path="/api/v1/dur/{id}",
     *    operationId="fetch_dur_by_id",
     *    tags={"Data Use Registers"},
     *    summary="DurController@show",
     *    description="Get dur by id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="data use register id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="data use register id",
     *       ),
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success"),
     *             @OA\Property(property="data", type="array",
     *                @OA\Items(type="object",
     *                   @OA\Property(property="id", type="integer", example="123"),
     *                   @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="non_gateway_datasets", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="non_gateway_applicants", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="funders_and_sponsors", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="other_approval_committees", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="gateway_outputs_tools", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="gateway_outputs_papers", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="non_gateway_outputs", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="project_title", type="string", example="Birth order and cord blood DNA methylation"),
     *                   @OA\Property(property="project_id_text", type="string", example="B3649"),
     *                   @OA\Property(property="organisation_name", type="string", example="LA-SER Europe Ltd"),
     *                   @OA\Property(property="organisation_sector", type="string", example="Independent Sector Organisation"),
     *                   @OA\Property(property="lay_summary", type="string", example="Non laudantium consequatur nulla minima. ..."),
     *                   @OA\Property(property="technical_summary", type="string", example="Sint odit veritatis nam excepturi natus. ..."),
     *                   @OA\Property(property="latest_approval_date", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="manual_upload", type="boolean", example="0"),
     *                   @OA\Property(property="rejection_reason", type="string", example=""),
     *                   @OA\Property(property="sublicence_arrangements", type="string", example=""),
     *                   @OA\Property(property="public_benefit_statement", type="string", example="Officiis provident sint iure. ..."),
     *                   @OA\Property(property="data_sensitivity_level", type="string", example="Anonymous"),
     *                   @OA\Property(property="project_start_date", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="project_end_date", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="access_date", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="accredited_researcher_status", type="string", example="No"),
     *                   @OA\Property(property="confidential_data_description", type="string", example=""),
     *                   @OA\Property(property="dataset_linkage_description", type="string", example=""),
     *                   @OA\Property(property="duty_of_confidentiality", type="string", example="Statutory exemption to flow confidential data without consent"),
     *                   @OA\Property(property="legal_basis_for_data_article6", type="string", example="Ad labore atque asperiores eum quia. ..."),
     *                   @OA\Property(property="legal_basis_for_data_article9", type="string", example="Quisquam illum ut porro quia. ..."),
     *                   @OA\Property(property="national_data_optout", type="string", example="Not applicable"),
     *                   @OA\Property(property="organisation_id", type="string", example="grid.10025.36"),
     *                   @OA\Property(property="privacy_enhancements", type="string", example="Voluptatem veritatis dolorem amet culpa qui qui. ..."),
     *                   @OA\Property(property="request_category_type", type="string", example="Health Services & Delivery"),
     *                   @OA\Property(property="request_frequency", type="string", example="Public Health Research"),
     *                   @OA\Property(property="access_type", type="string", example="Efficacy & Mechanism Evaluation"),
     *                   @OA\Property(property="mongo_object_dar_id", type="string", example="MOBJIDDAR-2387"),
     *                   @OA\Property(property="technicalSummary", type="string", example="Similique officia dolor nam. ..."),
     *                   @OA\Property(property="enabled", type="boolean", example="1"),
     *                   @OA\Property(property="last_activity", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="counter", type="integer", example="34319"),
     *                   @OA\Property(property="mongo_object_id", type="string", example="5f32a7d53b1d85c427e97c01"),
     *                   @OA\Property(property="mongo_id", type="string", example="38873389090594430"),
     *                   @OA\Property(property="datasets", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="keywords", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="users", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="applications", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="user", type="array", example="{}", @OA\Items()),
     *                   @OA\Property(property="team", type="array", example="{}", @OA\Items()),
     *                   @OA\Property(property="application", type="array", example="{}", @OA\Items()),
     *                   @OA\Property(property="applicant_id", type="string", example=""),
     *                ),
     *             ),
     *          ),
     *       ),
     *    ),
     * )
     */
    public function show(GetDur $request, int $id): JsonResponse
    {
        try {
            $dur = Dur::where(['id' => $id])
                ->with([
                    'datasets',
                    'keywords',
                    'users' => function ($query) {
                        $query->distinct('id');
                    },
                    'applications' => function ($query) {
                        $query->distinct('id');
                    },
                    'user',
                    'team',
                    'application',
                ])->get();

            return response()->json([
                'message' => 'success',
                'data' => $dur,
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *    path="/api/v1/dur",
     *    operationId="create_dur",
     *    tags={"Data Use Registers"},
     *    summary="DurController@store",
     *    description="Create a new dur",
     *    security={{"bearerAuth":{}}},
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(property="non_gateway_datasets", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="non_gateway_applicants", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="funders_and_sponsors", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="other_approval_committees", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="gateway_outputs_tools", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="gateway_outputs_papers", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="non_gateway_outputs", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="project_title", type="string", example="Birth order and cord blood DNA methylation"),
     *             @OA\Property(property="project_id_text", type="string", example="B3649"),
     *             @OA\Property(property="organisation_name", type="string", example="LA-SER Europe Ltd"),
     *             @OA\Property(property="organisation_sector", type="string", example="Independent Sector Organisation"),
     *             @OA\Property(property="lay_summary", type="string", example="Non laudantium consequatur nulla minima. ..."),
     *             @OA\Property(property="technical_summary", type="string", example="Sint odit veritatis nam excepturi natus. ..."),
     *             @OA\Property(property="latest_approval_date", type="datetime", example="2023-04-03 12:00:00"),
     *             @OA\Property(property="manual_upload", type="boolean", example="0"),
     *             @OA\Property(property="rejection_reason", type="string", example=""),
     *             @OA\Property(property="sublicence_arrangements", type="string", example=""),
     *             @OA\Property(property="public_benefit_statement", type="string", example="Officiis provident sint iure. ..."),
     *             @OA\Property(property="data_sensitivity_level", type="string", example="Anonymous"),
     *             @OA\Property(property="project_start_date", type="datetime", example="2023-04-03 12:00:00"),
     *             @OA\Property(property="project_end_date", type="datetime", example="2023-04-03 12:00:00"),
     *             @OA\Property(property="access_date", type="datetime", example="2023-04-03 12:00:00"),
     *             @OA\Property(property="accredited_researcher_status", type="string", example="No"),
     *             @OA\Property(property="confidential_data_description", type="string", example=""),
     *             @OA\Property(property="dataset_linkage_description", type="string", example=""),
     *             @OA\Property(property="duty_of_confidentiality", type="string", example="Statutory exemption to flow confidential data without consent"),
     *             @OA\Property(property="legal_basis_for_data_article6", type="string", example="Ad labore atque asperiores eum quia. ..."),
     *             @OA\Property(property="legal_basis_for_data_article9", type="string", example="Quisquam illum ut porro quia. ..."),
     *             @OA\Property(property="national_data_optout", type="string", example="Not applicable"),
     *             @OA\Property(property="organisation_id", type="string", example="grid.10025.36"),
     *             @OA\Property(property="privacy_enhancements", type="string", example="Voluptatem veritatis dolorem amet culpa qui qui. ..."),
     *             @OA\Property(property="request_category_type", type="string", example="Health Services & Delivery"),
     *             @OA\Property(property="request_frequency", type="string", example="Public Health Research"),
     *             @OA\Property(property="access_type", type="string", example="Efficacy & Mechanism Evaluation"),
     *             @OA\Property(property="mongo_object_dar_id", type="string", example="MOBJIDDAR-2387"),
     *             @OA\Property(property="technicalSummary", type="string", example="Similique officia dolor nam. ..."),
     *             @OA\Property(property="enabled", type="boolean", example="1"),
     *             @OA\Property(property="last_activity", type="datetime", example="2023-04-03 12:00:00"),
     *             @OA\Property(property="counter", type="integer", example="34319"),
     *             @OA\Property(property="mongo_object_id", type="string", example="5f32a7d53b1d85c427e97c01"),
     *             @OA\Property(property="mongo_id", type="string", example="38873389090594430"),
     *             @OA\Property(property="datasets", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="keywords", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="users", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="user", type="array", example="{}", @OA\Items()),
     *             @OA\Property(property="team", type="array", example="{}", @OA\Items()),
     *             @OA\Property(property="applicant_id", type="string", example=""),
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response=201,
     *       description="Created",
     *       @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="success"),
     *           @OA\Property(property="data", type="integer", example="100")
     *       )
     *    ),
     *    @OA\Response(
     *       response=401,
     *       description="Unauthorized",
     *       @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="unauthorized")
     *       )
     *    ),
     *    @OA\Response(
     *       response=500,
     *       description="Error",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="error"),
     *       )
     *    )
     * )
     */
    public function store(CreateDur $request): JsonResponse
    {
        try {
            $input = $request->all();

            $userId = null;
            $appId = null;
            $teamId = null;
            if (array_key_exists('user_id', $input)) {
                $userId = (int) $input['user_id'];
            } elseif (array_key_exists('jwt_user', $input)) {
                $userId = (int) $input['jwt_user']['id'];
            } else {
                $appId = (int) $input['app']['id'];
                $app = Application::where(['id' => $appId])->first();
                $userId = (int) $app->user_id;
                $teamId = (int) $app->team_id;
            }

            $arrayKeys = [
                'non_gateway_datasets', 
                'non_gateway_applicants',
                'funders_and_sponsors',
                'other_approval_committees',
                'gateway_outputs_tools',
                'gateway_outputs_papers',
                'non_gateway_outputs',
                'project_title',
                'project_id_text',
                'organisation_name',
                'organisation_sector',
                'lay_summary',
                'technical_summary',
                'latest_approval_date',
                'manual_upload',
                'rejection_reason',
                'sublicence_arrangements',
                'public_benefit_statement',
                'data_sensitivity_level',
                'accredited_researcher_status',
                'confidential_data_description',
                'dataset_linkage_description',
                'duty_of_confidentiality',
                'legal_basis_for_data_article6',
                'legal_basis_for_data_article9',
                'national_data_optout',
                'organisation_id',
                'privacy_enhancements',
                'request_category_type',
                'request_frequency',
                'access_type',
                'mongo_object_dar_id',
                'technicalSummary',
                'team_id',
                'enabled',
                'last_activity',
                'counter',
                'mongo_object_id',
                'mongo_id',
                'applicant_id',
            ];
            $array = $this->checkEditArray($input, $arrayKeys);
            $array['user_id'] = array_key_exists('user_id', $input) ? $input['user_id'] : $userId;
            $array['team_id'] = array_key_exists('team_id', $input) ? $input['team_id'] : $teamId;
            if ($appId) {
                $array['application_id'] = $appId;
            }

            if (!array_key_exists('team_id', $array)) {
                throw new NotFoundException("Team Id not found in request.");
            }

            $dur = Dur::create($array);
            $durId = $dur->id;

            // link/unlink dur with datasets
            $datasets = array_key_exists('datasets', $input) ? $input['datasets'] : [];
            $this->checkDatasets($durId, $datasets, $array['user_id'], $appId);

            // link/unlink dur with keywords
            $keywords = array_key_exists('keywords', $input) ? $input['keywords'] : [];
            $this->checkKeywords($durId, $keywords);

            // for migration from mongo database
            if (array_key_exists('created_at', $input)) {
                Dur::where('id', $durId)->update(['created_at' => $input['created_at']]);
            }

            // for migration from mongo database
            if (array_key_exists('updated_at', $input)) {
                Dur::where('id', $durId)->update(['updated_at' => $input['updated_at']]);
            }

            $this->indexElasticDur($durId);

            return response()->json([
                'message' => 'created',
                'data' => $durId,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *    path="/api/v1/dur/{id}",
     *    tags={"Data Use Registers"},
     *    summary="Update a dur by id",
     *    description="Update a dur",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="dur id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="dur id",
     *       ),
     *    ),
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(property="non_gateway_datasets", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="non_gateway_applicants", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="funders_and_sponsors", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="other_approval_committees", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="gateway_outputs_tools", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="gateway_outputs_papers", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="non_gateway_outputs", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="project_title", type="string", example="Birth order and cord blood DNA methylation"),
     *             @OA\Property(property="project_id_text", type="string", example="B3649"),
     *             @OA\Property(property="organisation_name", type="string", example="LA-SER Europe Ltd"),
     *             @OA\Property(property="organisation_sector", type="string", example="Independent Sector Organisation"),
     *             @OA\Property(property="lay_summary", type="string", example="Non laudantium consequatur nulla minima. ..."),
     *             @OA\Property(property="technical_summary", type="string", example="Sint odit veritatis nam excepturi natus. ..."),
     *             @OA\Property(property="latest_approval_date", type="datetime", example="2023-04-03 12:00:00"),
     *             @OA\Property(property="manual_upload", type="boolean", example="0"),
     *             @OA\Property(property="rejection_reason", type="string", example=""),
     *             @OA\Property(property="sublicence_arrangements", type="string", example=""),
     *             @OA\Property(property="public_benefit_statement", type="string", example="Officiis provident sint iure. ..."),
     *             @OA\Property(property="data_sensitivity_level", type="string", example="Anonymous"),
     *             @OA\Property(property="project_start_date", type="datetime", example="2023-04-03 12:00:00"),
     *             @OA\Property(property="project_end_date", type="datetime", example="2023-04-03 12:00:00"),
     *             @OA\Property(property="access_date", type="datetime", example="2023-04-03 12:00:00"),
     *             @OA\Property(property="accredited_researcher_status", type="string", example="No"),
     *             @OA\Property(property="confidential_data_description", type="string", example=""),
     *             @OA\Property(property="dataset_linkage_description", type="string", example=""),
     *             @OA\Property(property="duty_of_confidentiality", type="string", example="Statutory exemption to flow confidential data without consent"),
     *             @OA\Property(property="legal_basis_for_data_article6", type="string", example="Ad labore atque asperiores eum quia. ..."),
     *             @OA\Property(property="legal_basis_for_data_article9", type="string", example="Quisquam illum ut porro quia. ..."),
     *             @OA\Property(property="national_data_optout", type="string", example="Not applicable"),
     *             @OA\Property(property="organisation_id", type="string", example="grid.10025.36"),
     *             @OA\Property(property="privacy_enhancements", type="string", example="Voluptatem veritatis dolorem amet culpa qui qui. ..."),
     *             @OA\Property(property="request_category_type", type="string", example="Health Services & Delivery"),
     *             @OA\Property(property="request_frequency", type="string", example="Public Health Research"),
     *             @OA\Property(property="access_type", type="string", example="Efficacy & Mechanism Evaluation"),
     *             @OA\Property(property="mongo_object_dar_id", type="string", example="MOBJIDDAR-2387"),
     *             @OA\Property(property="technicalSummary", type="string", example="Similique officia dolor nam. ..."),
     *             @OA\Property(property="enabled", type="boolean", example="1"),
     *             @OA\Property(property="last_activity", type="datetime", example="2023-04-03 12:00:00"),
     *             @OA\Property(property="counter", type="integer", example="34319"),
     *             @OA\Property(property="mongo_object_id", type="string", example="5f32a7d53b1d85c427e97c01"),
     *             @OA\Property(property="mongo_id", type="string", example="38873389090594430"),
     *             @OA\Property(property="datasets", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="keywords", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="users", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="user", type="array", example="{}", @OA\Items()),
     *             @OA\Property(property="team", type="array", example="{}", @OA\Items()),
     *             @OA\Property(property="applicant_id", type="string", example=""),
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="Not found response",
     *       @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="not found")
     *       ),
     *    ),
     *    @OA\Response(
     *        response=200,
     *        description="Success",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="success"),
     *              @OA\Property(
     *                 property="data", type="object",
     *                   @OA\Property(property="id", type="integer", example="123"),
     *                   @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="non_gateway_datasets", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="non_gateway_applicants", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="funders_and_sponsors", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="other_approval_committees", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="gateway_outputs_tools", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="gateway_outputs_papers", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="non_gateway_outputs", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="project_title", type="string", example="Birth order and cord blood DNA methylation"),
     *                   @OA\Property(property="project_id_text", type="string", example="B3649"),
     *                   @OA\Property(property="organisation_name", type="string", example="LA-SER Europe Ltd"),
     *                   @OA\Property(property="organisation_sector", type="string", example="Independent Sector Organisation"),
     *                   @OA\Property(property="lay_summary", type="string", example="Non laudantium consequatur nulla minima. ..."),
     *                   @OA\Property(property="technical_summary", type="string", example="Sint odit veritatis nam excepturi natus. ..."),
     *                   @OA\Property(property="latest_approval_date", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="manual_upload", type="boolean", example="0"),
     *                   @OA\Property(property="rejection_reason", type="string", example=""),
     *                   @OA\Property(property="sublicence_arrangements", type="string", example=""),
     *                   @OA\Property(property="public_benefit_statement", type="string", example="Officiis provident sint iure. ..."),
     *                   @OA\Property(property="data_sensitivity_level", type="string", example="Anonymous"),
     *                   @OA\Property(property="project_start_date", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="project_end_date", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="access_date", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="accredited_researcher_status", type="string", example="No"),
     *                   @OA\Property(property="confidential_data_description", type="string", example=""),
     *                   @OA\Property(property="dataset_linkage_description", type="string", example=""),
     *                   @OA\Property(property="duty_of_confidentiality", type="string", example="Statutory exemption to flow confidential data without consent"),
     *                   @OA\Property(property="legal_basis_for_data_article6", type="string", example="Ad labore atque asperiores eum quia. ..."),
     *                   @OA\Property(property="legal_basis_for_data_article9", type="string", example="Quisquam illum ut porro quia. ..."),
     *                   @OA\Property(property="national_data_optout", type="string", example="Not applicable"),
     *                   @OA\Property(property="organisation_id", type="string", example="grid.10025.36"),
     *                   @OA\Property(property="privacy_enhancements", type="string", example="Voluptatem veritatis dolorem amet culpa qui qui. ..."),
     *                   @OA\Property(property="request_category_type", type="string", example="Health Services & Delivery"),
     *                   @OA\Property(property="request_frequency", type="string", example="Public Health Research"),
     *                   @OA\Property(property="access_type", type="string", example="Efficacy & Mechanism Evaluation"),
     *                   @OA\Property(property="mongo_object_dar_id", type="string", example="MOBJIDDAR-2387"),
     *                   @OA\Property(property="technicalSummary", type="string", example="Similique officia dolor nam. ..."),
     *                   @OA\Property(property="enabled", type="boolean", example="1"),
     *                   @OA\Property(property="last_activity", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="counter", type="integer", example="34319"),
     *                   @OA\Property(property="mongo_object_id", type="string", example="5f32a7d53b1d85c427e97c01"),
     *                   @OA\Property(property="mongo_id", type="string", example="38873389090594430"),
     *                   @OA\Property(property="datasets", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="keywords", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="users", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="applications", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="user", type="array", example="{}", @OA\Items()),
     *                   @OA\Property(property="team", type="array", example="{}", @OA\Items()),
     *                   @OA\Property(property="application", type="array", example="{}", @OA\Items()),
     *                   @OA\Property(property="applicant_id", type="string", example=""),
     *              ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error")
     *          )
     *      )
     * )
     */
    public function update(UpdateDur $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();

            $userId = null;
            $appId = null;
            if (array_key_exists('user_id', $input)) {
                $userId = (int) $input['user_id'];
            } elseif (array_key_exists('jwt_user', $input)) {
                $userId = (int) $input['jwt_user']['id'];
            } else {
                $appId = (int) $input['app']['id'];
            }

            $arrayKeys = [
                'non_gateway_datasets',
                'non_gateway_applicants',
                'funders_and_sponsors',
                'other_approval_committees',
                'gateway_outputs_tools',
                'gateway_outputs_papers',
                'non_gateway_outputs',
                'project_title',
                'project_id_text',
                'organisation_name',
                'organisation_sector',
                'lay_summary',
                'technical_summary',
                'latest_approval_date',
                'manual_upload',
                'rejection_reason',
                'sublicence_arrangements',
                'public_benefit_statement',
                'data_sensitivity_level',
                'accredited_researcher_status',
                'confidential_data_description',
                'dataset_linkage_description',
                'duty_of_confidentiality',
                'legal_basis_for_data_article6',
                'legal_basis_for_data_article9',
                'national_data_optout',
                'organisation_id',
                'privacy_enhancements',
                'request_category_type',
                'request_frequency',
                'access_type',
                'mongo_object_dar_id',
                'technicalSummary',
                'enabled',
                'last_activity',
                'counter',
                'mongo_object_id',
                'mongo_id',
                'applicant_id',
            ];
            $array = $this->checkEditArray($input, $arrayKeys);
            $userIdFinal = array_key_exists('user_id', $input) ? $input['user_id'] : $userId;

            Dur::where('id', $id)->update($array);

            // link/unlink dur with datasets
            $datasets = array_key_exists('datasets', $input) ? $input['datasets'] : [];
            $this->checkDatasets($id, $datasets, $userIdFinal, $appId);

            // link/unlink dur with keywords
            $keywords = array_key_exists('keywords', $input) ? $input['keywords'] : [];
            $this->checkKeywords($id, $keywords);

            // for migration from mongo database
            if (array_key_exists('created_at', $input)) {
                Dur::where('id', $id)->update(['created_at' => $input['created_at']]);
            }

            // for migration from mongo database
            if (array_key_exists('updated_at', $input)) {
                Dur::where('id', $id)->update(['updated_at' => $input['updated_at']]);
            }

            $this->indexElasticDur($id);

            return response()->json([
                'message' => 'success',
                'data' => Dur::where('id', $id)->with([
                    'datasets',
                    'keywords',
                    'users' => function ($query) {
                        $query->distinct('id');
                    },
                    'applications' => function ($query) {
                        $query->distinct('id');
                    },
                    'user',
                    'team',
                    'application',
                ])->first(),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *    path="/api/v1/dur/{id}",
     *    tags={"Data Use Registers"},
     *    summary="Edit a dur",
     *    description="Edit a dur",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="dur id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="dur id",
     *       ),
     *    ),
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(property="non_gateway_datasets", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="non_gateway_applicants", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="funders_and_sponsors", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="other_approval_committees", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="gateway_outputs_tools", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="gateway_outputs_papers", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="non_gateway_outputs", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="project_title", type="string", example="Birth order and cord blood DNA methylation"),
     *             @OA\Property(property="project_id_text", type="string", example="B3649"),
     *             @OA\Property(property="organisation_name", type="string", example="LA-SER Europe Ltd"),
     *             @OA\Property(property="organisation_sector", type="string", example="Independent Sector Organisation"),
     *             @OA\Property(property="lay_summary", type="string", example="Non laudantium consequatur nulla minima. ..."),
     *             @OA\Property(property="technical_summary", type="string", example="Sint odit veritatis nam excepturi natus. ..."),
     *             @OA\Property(property="latest_approval_date", type="datetime", example="2023-04-03 12:00:00"),
     *             @OA\Property(property="manual_upload", type="boolean", example="0"),
     *             @OA\Property(property="rejection_reason", type="string", example=""),
     *             @OA\Property(property="sublicence_arrangements", type="string", example=""),
     *             @OA\Property(property="public_benefit_statement", type="string", example="Officiis provident sint iure. ..."),
     *             @OA\Property(property="data_sensitivity_level", type="string", example="Anonymous"),
     *             @OA\Property(property="project_start_date", type="datetime", example="2023-04-03 12:00:00"),
     *             @OA\Property(property="project_end_date", type="datetime", example="2023-04-03 12:00:00"),
     *             @OA\Property(property="access_date", type="datetime", example="2023-04-03 12:00:00"),
     *             @OA\Property(property="accredited_researcher_status", type="string", example="No"),
     *             @OA\Property(property="confidential_data_description", type="string", example=""),
     *             @OA\Property(property="dataset_linkage_description", type="string", example=""),
     *             @OA\Property(property="duty_of_confidentiality", type="string", example="Statutory exemption to flow confidential data without consent"),
     *             @OA\Property(property="legal_basis_for_data_article6", type="string", example="Ad labore atque asperiores eum quia. ..."),
     *             @OA\Property(property="legal_basis_for_data_article9", type="string", example="Quisquam illum ut porro quia. ..."),
     *             @OA\Property(property="national_data_optout", type="string", example="Not applicable"),
     *             @OA\Property(property="organisation_id", type="string", example="grid.10025.36"),
     *             @OA\Property(property="privacy_enhancements", type="string", example="Voluptatem veritatis dolorem amet culpa qui qui. ..."),
     *             @OA\Property(property="request_category_type", type="string", example="Health Services & Delivery"),
     *             @OA\Property(property="request_frequency", type="string", example="Public Health Research"),
     *             @OA\Property(property="access_type", type="string", example="Efficacy & Mechanism Evaluation"),
     *             @OA\Property(property="mongo_object_dar_id", type="string", example="MOBJIDDAR-2387"),
     *             @OA\Property(property="technicalSummary", type="string", example="Similique officia dolor nam. ..."),
     *             @OA\Property(property="enabled", type="boolean", example="1"),
     *             @OA\Property(property="last_activity", type="datetime", example="2023-04-03 12:00:00"),
     *             @OA\Property(property="counter", type="integer", example="34319"),
     *             @OA\Property(property="mongo_object_id", type="string", example="5f32a7d53b1d85c427e97c01"),
     *             @OA\Property(property="mongo_id", type="string", example="38873389090594430"),
     *             @OA\Property(property="datasets", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="keywords", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="users", type="array", example="[]", @OA\Items()),
     *             @OA\Property(property="user", type="array", example="{}", @OA\Items()),
     *             @OA\Property(property="team", type="array", example="{}", @OA\Items()),
     *             @OA\Property(property="applicant_id", type="string", example=""),
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="Not found response",
     *       @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="not found")
     *       ),
     *    ),
     *    @OA\Response(
     *        response=200,
     *        description="Success",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="success"),
     *              @OA\Property(
     *                 property="data", type="object",
     *                   @OA\Property(property="id", type="integer", example="123"),
     *                   @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="non_gateway_datasets", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="non_gateway_applicants", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="funders_and_sponsors", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="other_approval_committees", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="gateway_outputs_tools", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="gateway_outputs_papers", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="non_gateway_outputs", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="project_title", type="string", example="Birth order and cord blood DNA methylation"),
     *                   @OA\Property(property="project_id_text", type="string", example="B3649"),
     *                   @OA\Property(property="organisation_name", type="string", example="LA-SER Europe Ltd"),
     *                   @OA\Property(property="organisation_sector", type="string", example="Independent Sector Organisation"),
     *                   @OA\Property(property="lay_summary", type="string", example="Non laudantium consequatur nulla minima. ..."),
     *                   @OA\Property(property="technical_summary", type="string", example="Sint odit veritatis nam excepturi natus. ..."),
     *                   @OA\Property(property="latest_approval_date", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="manual_upload", type="boolean", example="0"),
     *                   @OA\Property(property="rejection_reason", type="string", example=""),
     *                   @OA\Property(property="sublicence_arrangements", type="string", example=""),
     *                   @OA\Property(property="public_benefit_statement", type="string", example="Officiis provident sint iure. ..."),
     *                   @OA\Property(property="data_sensitivity_level", type="string", example="Anonymous"),
     *                   @OA\Property(property="project_start_date", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="project_end_date", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="access_date", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="accredited_researcher_status", type="string", example="No"),
     *                   @OA\Property(property="confidential_data_description", type="string", example=""),
     *                   @OA\Property(property="dataset_linkage_description", type="string", example=""),
     *                   @OA\Property(property="duty_of_confidentiality", type="string", example="Statutory exemption to flow confidential data without consent"),
     *                   @OA\Property(property="legal_basis_for_data_article6", type="string", example="Ad labore atque asperiores eum quia. ..."),
     *                   @OA\Property(property="legal_basis_for_data_article9", type="string", example="Quisquam illum ut porro quia. ..."),
     *                   @OA\Property(property="national_data_optout", type="string", example="Not applicable"),
     *                   @OA\Property(property="organisation_id", type="string", example="grid.10025.36"),
     *                   @OA\Property(property="privacy_enhancements", type="string", example="Voluptatem veritatis dolorem amet culpa qui qui. ..."),
     *                   @OA\Property(property="request_category_type", type="string", example="Health Services & Delivery"),
     *                   @OA\Property(property="request_frequency", type="string", example="Public Health Research"),
     *                   @OA\Property(property="access_type", type="string", example="Efficacy & Mechanism Evaluation"),
     *                   @OA\Property(property="mongo_object_dar_id", type="string", example="MOBJIDDAR-2387"),
     *                   @OA\Property(property="technicalSummary", type="string", example="Similique officia dolor nam. ..."),
     *                   @OA\Property(property="enabled", type="boolean", example="1"),
     *                   @OA\Property(property="last_activity", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="counter", type="integer", example="34319"),
     *                   @OA\Property(property="mongo_object_id", type="string", example="5f32a7d53b1d85c427e97c01"),
     *                   @OA\Property(property="mongo_id", type="string", example="38873389090594430"),
     *                   @OA\Property(property="datasets", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="keywords", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="users", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="applications", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="user", type="array", example="{}", @OA\Items()),
     *                   @OA\Property(property="team", type="array", example="{}", @OA\Items()),
     *                   @OA\Property(property="application", type="array", example="{}", @OA\Items()),
     *                   @OA\Property(property="applicant_id", type="string", example=""),
     *              ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error")
     *          )
     *      )
     * )
     */
    public function edit(EditDur $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();

            $userId = null;
            $appId = null;
            if (array_key_exists('user_id', $input)) {
                $userId = (int) $input['user_id'];
            } elseif (array_key_exists('jwt_user', $input)) {
                $userId = (int) $input['jwt_user']['id'];
            } else {
                $appId = (int) $input['app']['id'];
            }

            $arrayKeys = [
                'non_gateway_datasets',
                'non_gateway_applicants',
                'funders_and_sponsors',
                'other_approval_committees',
                'gateway_outputs_tools',
                'gateway_outputs_papers',
                'non_gateway_outputs',
                'project_title',
                'project_id_text',
                'organisation_name',
                'organisation_sector',
                'lay_summary',
                'technical_summary',
                'latest_approval_date',
                'manual_upload',
                'rejection_reason',
                'sublicence_arrangements',
                'public_benefit_statement',
                'data_sensitivity_level',
                'accredited_researcher_status',
                'confidential_data_description',
                'dataset_linkage_description',
                'duty_of_confidentiality',
                'legal_basis_for_data_article6',
                'legal_basis_for_data_article9',
                'national_data_optout',
                'organisation_id',
                'privacy_enhancements',
                'request_category_type',
                'request_frequency',
                'access_type',
                'mongo_object_dar_id',
                'technicalSummary',
                'enabled',
                'last_activity',
                'counter',
                'mongo_object_id',
                'mongo_id',
                'applicant_id',
            ];
            $array = $this->checkEditArray($input, $arrayKeys);
            $userIdFinal = array_key_exists('user_id', $input) ? $input['user_id'] : $userId;

            Dur::where('id', $id)->update($array);

            // link/unlink dur with datasets
            if (array_key_exists('datasets', $input)) {
                $datasets = $input['datasets'];
                $this->checkDatasets($id, $datasets, $userIdFinal, $appId);
            }

            // link/unlink dur with keywords
            if (array_key_exists('keywords', $input)) {
                $keywords = $input['keywords'];
                $this->checkKeywords($id, $keywords);
            }

            // for migration from mongo database
            if (array_key_exists('created_at', $input)) {
                Dur::where('id', $id)->update(['created_at' => $input['created_at']]);
            }

            // for migration from mongo database
            if (array_key_exists('updated_at', $input)) {
                Dur::where('id', $id)->update(['updated_at' => $input['updated_at']]);
            }

            $this->indexElasticDur($id);

            return response()->json([
                'message' => 'success',
                'data' => Dur::where('id', $id)->with([
                    'datasets',
                    'keywords',
                    'users' => function ($query) {
                        $query->distinct('id');
                    },
                    'applications' => function ($query) {
                        $query->distinct('id');
                    },
                    'user',
                    'team',
                    'application',
                ])->first(),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *    path="/api/v1/dur/{id}",
     *    tags={"Data Use Registers"},
     *    summary="Delete a dur",
     *    description="Delete a dur",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="dur id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="dur id",
     *       ),
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="Not found response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="not found")
     *       ),
     *    ),
     *    @OA\Response(
     *       response=200,
     *       description="Success",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success")
     *       ),
     *    ),
     *    @OA\Response(
     *       response=500,
     *       description="Error",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="error")
     *       )
     *    )
     * )
     */
    public function destroy(DeleteDur $request, int $id): JsonResponse
    {
        try {
            DurHasDataset::where(['dur_id' => $id])->delete();
            DurHasKeyword::where(['dur_id' => $id])->delete();
            Dur::where(['id' => $id])->delete();

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    private function checkDatasets(int $durId, array $inDatasets, int $userId = null, int $appId = null) 
    {
        $ds = DurHasDataset::where(['dur_id' => $durId])->get();
        foreach ($ds as $d) {
            if (!in_array($d->dataset_id, $this->extractInputDatasetIdToArray($inDatasets))) {
                $this->deleteDurHasDatasets($durId, $d->dataset_id);
            }
        }

        foreach ($inDatasets as $dataset) {
            $checking = $this->checkInDurHasDatasets($durId, (int) $dataset['id']);

            if (!$checking) {
                $this->addDurHasDataset($durId, $dataset, $userId, $appId);
            }
        }
    }

    private function addDurHasDataset(int $durId, array $dataset, int $userId = null, int $appId = null)
    {
        try {
            $arrCreate = [
                'dur_id' => $durId,
                'dataset_id' => $dataset['id'],
            ];

            if (array_key_exists('user_id', $dataset)) {
                $arrCreate['user_id'] = (int) $dataset['user_id'];
            } elseif ($userId) {
                $arrCreate['user_id'] = $userId;
            }

            if (array_key_exists('reason', $dataset)) {
                $arrCreate['reason'] = $dataset['reason'];
            }

            if (array_key_exists('updated_at', $dataset)) { // special for migration
                $arrCreate['created_at'] = $dataset['updated_at'];
                $arrCreate['updated_at'] = $dataset['updated_at'];
            }

            if (array_key_exists('is_locked', $dataset)) {
                $arrCreate['is_locked'] = (bool) $dataset['is_locked'];
            }

            if ($appId) {
                $arrCreate['application_id'] = $appId;
            }

            return DurHasDataset::updateOrCreate(
                $arrCreate,
                [
                    'dur_id' => $durId,
                    'dataset_id' => $dataset['id'],
                ]
            );
        } catch (Exception $e) {
            throw new Exception("addDurHasDataset :: " . $e->getMessage());
        }
    }

    private function checkInDurHasDatasets(int $durId, int $datasetId)
    {
        try {
            return DurHasDataset::where([
                'dur_id' => $durId,
                'dataset_id' => $datasetId,
            ])->first();
        } catch (Exception $e) {
            throw new Exception("checkInDurHasDatasets :: " . $e->getMessage());
        }
    }

    private function deleteDurHasDatasets(int $durId, int $datasetId)
    {
        try {
            return DurHasDataset::where([
                'dur_id' => $durId,
                'dataset_id' => $datasetId,
            ])->delete();
        } catch (Exception $e) {
            throw new Exception("deleteKeywordDur :: " . $e->getMessage());
        }
    }

    private function checkKeywords(int $durId, array $inKeywords)
    {
        $kws = DurHasKeyword::where('dur_id', $durId)->get();

        foreach($kws as $kw) {
            $kwId = $kw->keyword_id;
            $checkKeyword = Keyword::where('id', $kwId)->first();

            if (!$checkKeyword) {
                $this->deleteDurHasKeywords($kwId);
                continue;
            }

            if (in_array($checkKeyword->name, $inKeywords)) continue;

            if (!in_array($checkKeyword->name, $inKeywords)) {
                $this->deleteDurHasKeywords($kwId);
            }
        }

        foreach ($inKeywords as $keyword) {
            $keywordId = $this->updateOrCreateKeyword($keyword)->id;
            $this->updateOrCreateDurHasKeywords($durId, $keywordId);
        }
    }

    private function updateOrCreateDurHasKeywords(int $durId, int $keywordId)
    {
        try {
            return DurHasKeyword::updateOrCreate([
                'dur_id' => $durId,
                'keyword_id' => $keywordId,
            ]);
        } catch (Exception $e) {
            throw new Exception("addKeywordDur :: " . $e->getMessage());
        }
    }

    private function updateOrCreateKeyword($keyword)
    {
        try {
            return Keyword::updateOrCreate([
                'name' => $keyword,
            ],[
                'name' => $keyword,
                'enabled' => 1,
            ]);
        } catch (Exception $e) {
            throw new Exception("createUpdateKeyword :: " . $e->getMessage());
        }
    } 

    private function deleteDurHasKeywords($keywordId)
    {
        try {
            return DurHasKeyword::where(['keyword_id' => $keywordId])->delete();
        } catch (Exception $e) {
            throw new Exception("deleteKeywordDur :: " . $e->getMessage());
        }
    }

    private function extractInputDatasetIdToArray(array $inputDatasets): Array
    {
        $response = [];
        foreach ($inputDatasets as $inputDataset) {
            $response[] = $inputDataset['id'];
        }

        return $response;
    }

    /**
     * Calls a re-indexing of Elastic search when a data use is created or updated
     * 
     * @param string $id The dur id from the DB
     * 
     * @return void
     */
    public function indexElasticDur(string $id): void
    {
        try {

            $durMatch = Dur::where(['id' => $id])
                ->with(['datasets', 'keywords'])
                ->first()
                ->toArray();

            $datasetTitles = array();
            foreach ($durMatch['datasets'] as $d) {
                $metadata = Dataset::where(['id' => $d])
                    ->first()
                    ->latestVersion()
                    ->metadata;
                $datasetTitles[] = $metadata['metadata']['summary']['shortTitle'];
            }

            $keywords = array();
            foreach ($durMatch['keywords'] as $k) {
                $keywords[] = $k['name'];
            }

            $toIndex = [
                'projectTitle' => $durMatch['project_title'],
                'laySummary' => $durMatch['lay_summary'],
                'publicBenefitStatement' => $durMatch['public_benefit_statement'],
                'technicalSummary' => $durMatch['technical_summary'],
                'fundersAndSponsors' => $durMatch['funders_and_sponsors'],
                'datasetTitles' => $datasetTitles,
                'keywords' => $keywords
            ];

            $params = [
                'index' => 'data_uses',
                'id' => $id,
                'body' => $toIndex,
                'headers' => 'application/json'
            ];
            
            $client = MMC::getElasticClient();
            $response = $client->index($params);

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
