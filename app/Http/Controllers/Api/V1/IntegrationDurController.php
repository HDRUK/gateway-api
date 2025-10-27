<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;
use App\Models\Dur;
use App\Models\Tool;
use App\Models\Sector;
use App\Models\Dataset;
use App\Models\Keyword;
use App\Models\DurHasTool;
use App\Models\Application;
use Illuminate\Http\Request;
use App\Models\DurHasKeyword;
use App\Models\DurHasDatasetVersion;
use App\Models\DatasetVersion;
use App\Http\Requests\Dur\GetDur;
use App\Models\DurHasPublication;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Dur\EditDur;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dur\CreateDur;
use App\Http\Requests\Dur\DeleteDur;
use App\Http\Requests\Dur\UpdateDur;
use App\Exceptions\NotFoundException;
use App\Http\Traits\IntegrationOverride;
use App\Http\Traits\RequestTransformation;

class IntegrationDurController extends Controller
{
    use RequestTransformation;
    use IntegrationOverride;

    /**
     * @OA\Get(
     *    path="/api/v1/integrations/dur",
     *    deprecated=true,
     *    operationId="fetch_all_dur_integrations",
     *    tags={"Integration Data Use Registers"},
     *    summary="IntegrationDurController@index",
     *    description="Returns a list of dur",
     *    @OA\Parameter(
     *       name="sort",
     *       in="query",
     *       description="Sort fields in the format field:direction, e.g., project_title:asc,updated_at:asc",
     *       required=false,
     *       @OA\Schema(
     *           type="project_title:asc,updated_at:asc"
     *       )
     *    ),
     *    @OA\Parameter(
     *          name="per_page",
     *          in="query",
     *          description="per page",
     *          required=false,
     *          example="1",
     *          @OA\Schema(
     *              type="integer",
     *              description="per page",
     *          ),
     *      ),
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
     *                @OA\Property(property="enabled", type="boolean", example="1"),
     *                @OA\Property(property="last_activity", type="datetime", example="2023-04-03 12:00:00"),
     *                @OA\Property(property="counter", type="integer", example="34319"),
     *                @OA\Property(property="mongo_object_id", type="string", example="5f32a7d53b1d85c427e97c01"),
     *                @OA\Property(property="mongo_id", type="string", example="38873389090594430"),
     *                @OA\Property(property="datasets", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="publications", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="tools", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="keywords", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="users", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="user", type="array", example="{}", @OA\Items()),
     *                @OA\Property(property="team", type="array", example="{}", @OA\Items()),
     *                @OA\Property(property="application", type="string", example=""),
     *                @OA\Property(property="status", type="string", enum={"ACTIVE", "DRAFT", "ARCHIVED"}),
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
        try {
            $input = $request->all();

            $sort = [];
            $sortArray = $request->has('sort') ? explode(',', $request->query('sort', '')) : [];
            foreach ($sortArray as $item) {
                $tmp = explode(":", $item);
                $sort[$tmp[0]] = array_key_exists('1', $tmp) ? $tmp[1] : 'asc';
            }
            if (!array_key_exists('updated_at', $sort)) {
                $sort['updated_at'] = 'desc';
            }

            $applicationOverrideDefaultValues = $this->injectApplicationDatasetDefaults($request->header());

            $perPage = request('per_page', Config::get('constants.per_page'));
            $durs = Dur::where('enabled', 1)
                ->with([
                    'publications',
                    'tools',
                    'keywords',
                    'userDatasets' => function ($query) {
                        $query->distinct('id');
                    },
                    'userPublications' => function ($query) {
                        $query->distinct('id');
                    },
                    'applicationDatasets' => function ($query) {
                        $query->distinct('id');
                    },
                    'applicationPublications' => function ($query) {
                        $query->distinct('id');
                    },
                    'user',
                    'team',
                    'application',
                ]);

            foreach ($sort as $key => $value) {
                $durs->orderBy('dur.' . $key, strtoupper($value));
            }

            $durs = $durs->paginate((int) $perPage, ['*'], 'page');

            $durs->getCollection()->transform(function ($dur) {
                $userDatasets = $dur->userDatasets;
                $userPublications = $dur->userPublications;
                $users = $userDatasets->merge($userPublications)->unique('id');
                $dur->setRelation('users', $users);

                $applicationDatasets = $dur->applicationDatasets;
                $applicationPublications = $dur->applicationPublications;
                $dur->setAttribute('datasets', $dur->allDatasets  ?? []);
                $applications = $applicationDatasets->merge($applicationPublications)->unique('id');
                $dur->setRelation('applications', $applications);

                unset(
                    $users,
                    $userDatasets,
                    $userPublications,
                    $applications,
                    $applicationDatasets,
                    $applicationPublications,
                    $dur->userDatasets,
                    $dur->userPublications,
                    $dur->applicationDatasets,
                    $dur->applicationPublications
                );

                return $dur;
            });

            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Dur Integration get all',
            ]);

            return response()->json(
                $durs
            );
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *    path="/api/v1/integrations/dur/{id}",
     *    deprecated=true,
     *    operationId="fetch_dur_by_id_integrations",
     *    tags={"Integration Data Use Registers"},
     *    summary="IntegrationDurController@show",
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
     *                   @OA\Property(property="enabled", type="boolean", example="1"),
     *                   @OA\Property(property="last_activity", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="counter", type="integer", example="34319"),
     *                   @OA\Property(property="mongo_object_id", type="string", example="5f32a7d53b1d85c427e97c01"),
     *                   @OA\Property(property="mongo_id", type="string", example="38873389090594430"),
     *                   @OA\Property(property="datasets", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="publications", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="tools", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="keywords", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="users", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="applications", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="user", type="array", example="{}", @OA\Items()),
     *                   @OA\Property(property="team", type="array", example="{}", @OA\Items()),
     *                   @OA\Property(property="application", type="array", example="{}", @OA\Items()),
     *                   @OA\Property(property="applicantions", type="string", example=""),
     *                   @OA\Property(property="status", type="string", enum={"ACTIVE", "DRAFT", "ARCHIVED"}),
     *                ),
     *             ),
     *          ),
     *       ),
     *    ),
     * )
     */
    public function show(GetDur $request, int $id): JsonResponse
    {
        $input = $request->all();

        try {
            $applicationOverrideDefaultValues = $this->injectApplicationDatasetDefaults($request->header());

            $dur = $this->getDurById($id);

            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Dur Integration get ' . $id,
            ]);

            return response()->json([
                'message' => 'success',
                'data' => [$dur],
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *    path="/api/v1/integrations/dur",
     *    operationId="create_dur_integrations",
     *    tags={"Integration Data Use Registers"},
     *    summary="IntegrationDurController@store",
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
     *             @OA\Property(property="status", type="string", enum={"ACTIVE", "DRAFT", "ARCHIVED"}),
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
        $input = $request->all();
        $applicationOverrideDefaultValues = $this->injectApplicationDatasetDefaults($request->header());
        try {
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
                'team_id',
                'enabled',
                'last_activity',
                'counter',
                'mongo_object_id',
                'mongo_id',
                'applicant_id',
                'status',
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

            if (array_key_exists('organisation_sector', $array)) {
                $array['sector_id'] = $this->mapOrganisationSector($array['organisation_sector']);
            }

            $dur = Dur::create($array);
            $durId = $dur->id;

            // link/unlink dur with datasets
            $datasets = array_key_exists('datasets', $input) ? $input['datasets'] : [];
            $this->checkDatasets($durId, $datasets, $array['user_id'], $appId);

            // link/unlink dur with keywords
            $keywords = array_key_exists('keywords', $input) ? $input['keywords'] : [];
            $this->checkKeywords($durId, $keywords);

            // link/unlink dur with tools
            $tools = array_key_exists('tools', $input) ? $input['tools'] : [];
            $this->checkTools($durId, $tools);

            // for migration from mongo database
            if (array_key_exists('created_at', $input)) {
                Dur::where('id', $durId)->update(['created_at' => $input['created_at']]);
            }

            // for migration from mongo database
            if (array_key_exists('updated_at', $input)) {
                Dur::where('id', $durId)->update(['updated_at' => $input['updated_at']]);
            }

            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Dur Integration ' . $durId . ' created',
            ]);

            return response()->json([
                'message' => 'created',
                'data' => $durId,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *    path="/api/v1/integrations/dur/{id}",
     *    deprecated=true,
     *    tags={"Integration Data Use Registers"},
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
     *             @OA\Property(property="status", type="string", enum={"ACTIVE", "DRAFT", "ARCHIVED"}),
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
     *                   @OA\Property(property="status", type="string", enum={"ACTIVE", "DRAFT", "ARCHIVED"}),
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
        $input = $request->all();
        $applicationOverrideDefaultValues = $this->injectApplicationDatasetDefaults($request->header());
        $initDur = Dur::withTrashed()->where('id', $id)->first();

        try {
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
                'enabled',
                'last_activity',
                'counter',
                'mongo_object_id',
                'mongo_id',
                'applicant_id',
                'status',
            ];
            $array = $this->checkEditArray($input, $arrayKeys);

            if ($initDur === 'ARCHIVED' && !array_key_exists('status', $input)) {
                throw new Exception('Cannot update current data use register! Status already "ARCHIVED"');
            }

            if ($initDur === 'ARCHIVED' && (array_key_exists('status', $input) && $input['status'] !== 'ARCHIVED')) {
                Dur::withTrashed()->where('id', $id)->restore();
                DurHasDatasetVersion::withTrashed()->where('dur_id', $id)->restore();
                DurHasKeyword::withTrashed()->where('dur_id', $id)->restore();
                DurHasPublication::withTrashed()->where('dur_id', $id)->restore();
                DurHasTool::withTrashed()->where('dur_id', $id)->restore();
            }

            $userIdFinal = array_key_exists('user_id', $input) ? $input['user_id'] : $userId;

            if (array_key_exists('organisation_sector', $array)) {
                $array['sector_id'] = $this->mapOrganisationSector($array['organisation_sector']);
            }

            Dur::where('id', $id)->update($array);

            // link/unlink dur with datasets
            $datasets = array_key_exists('datasets', $input) ? $input['datasets'] : [];
            $this->checkDatasets($id, $datasets, $userIdFinal, $appId);

            // link/unlink dur with publications
            $publications = array_key_exists('publications', $input) ? $input['publications'] : [];
            $this->checkPublications($id, $publications, $userIdFinal, $appId);

            // link/unlink dur with keywords
            $keywords = array_key_exists('keywords', $input) ? $input['keywords'] : [];
            $this->checkKeywords($id, $keywords);

            // link/unlink dur with tools
            $tools = array_key_exists('tools', $input) ? $input['tools'] : [];
            $this->checkTools($id, $tools);

            // for migration from mongo database
            if (array_key_exists('created_at', $input)) {
                Dur::where('id', $id)->update(['created_at' => $input['created_at']]);
            }

            // for migration from mongo database
            if (array_key_exists('updated_at', $input)) {
                Dur::where('id', $id)->update(['updated_at' => $input['updated_at']]);
            }

            $currentDurStatus = Dur::where('id', $id)->first();
            if ($currentDurStatus->status === 'ARCHIVED') {
                Dur::where('id', $id)->delete();
                DurHasDatasetVersion::where('dur_id', $id)->delete();
                DurHasKeyword::where('dur_id', $id)->delete();
                DurHasPublication::where('dur_id', $id)->delete();
                DurHasTool::where('dur_id', $id)->delete();
            }

            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Dur Integration ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $this->getDurById($id),
            ], Config::get('statuscodes.STATUS_OK.code'));
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
     * @OA\Patch(
     *    path="/api/v1/integrations/dur/{id}",
     *    deprecated=true,
     *    tags={"Integration Data Use Registers"},
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
     *             @OA\Property(property="status", type="string", enum={"ACTIVE", "DRAFT", "ARCHIVED"}),
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
     *                   @OA\Property(property="status", type="string", enum={"ACTIVE", "DRAFT", "ARCHIVED"}),
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
        $input = $request->all();
        $applicationOverrideDefaultValues = $this->injectApplicationDatasetDefaults($request->header());

        try {
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
                'enabled',
                'last_activity',
                'counter',
                'mongo_object_id',
                'mongo_id',
                'applicant_id',
                'status',
            ];
            $array = $this->checkEditArray($input, $arrayKeys);
            $userIdFinal = array_key_exists('user_id', $input) ? $input['user_id'] : $userId;

            if (array_key_exists('organisation_sector', $array)) {
                $array['sector_id'] = $this->mapOrganisationSector($array['organisation_sector']);
            }

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

            // link/unlink dur with tools
            if (array_key_exists('tools', $input)) {
                $tools = $input['tools'];
                $this->checkKeywords($id, $tools);
            }

            // for migration from mongo database
            if (array_key_exists('created_at', $input)) {
                Dur::where('id', $id)->update(['created_at' => $input['created_at']]);
            }

            // for migration from mongo database
            if (array_key_exists('updated_at', $input)) {
                Dur::where('id', $id)->update(['updated_at' => $input['updated_at']]);
            }

            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Dur Integration ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $this->getDurById($id),
            ], Config::get('statuscodes.STATUS_OK.code'));
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
     * @OA\Delete(
     *    path="/api/v1/integrations/dur/{id}",
     *    deprecated=true,
     *    tags={"Integration Data Use Registers"},
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
            $input = $request->all();
            $applicationOverrideDefaultValues = $this->injectApplicationDatasetDefaults($request->header());

            DurHasDatasetVersion::where(['dur_id' => $id])->delete();
            DurHasKeyword::where(['dur_id' => $id])->delete();
            DurHasTool::where(['dur_id' => $id])->delete();
            Dur::where(['id' => $id])->delete();

            Auditor::log([
                'user_id' => (isset($applicationOverrideDefaultValues['user_id']) ?
                    $applicationOverrideDefaultValues['user_id'] : $input['user_id']),
                'team_id' => (isset($applicationOverrideDefaultValues['team_id']) ?
                    $applicationOverrideDefaultValues['team_id'] : $input['team_id']),
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Dur Integration ' . $id . ' deleted',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    // datasets
    private function checkDatasets(int $durId, array $inDatasets, ?int $userId = null, ?int $appId = null)
    {
        $durDatasets = DurHasDatasetVersion::where(['dur_id' => $durId])->get();
        foreach ($durDatasets as $durDataset) {
            $dataset_id = DatasetVersion::where("id", $durDataset->dataset_version_id)->first()->dataset_id;
            if (!in_array($dataset_id, $this->extractInputIdToArray($inDatasets))) {
                $this->deleteDurHasDatasetVersion($durId, $durDataset->dataset_version_id);
            }
        }

        foreach ($inDatasets as $dataset) {
            $datasetVersionId = Dataset::where('id', (int) $dataset['id'])->first()->latestVersion()->id;
            $checking = $this->checkInDurHasDatasetVersion($durId, $datasetVersionId);

            if (!$checking) {
                $this->addDurHasDatasetVersion($durId, $dataset, $datasetVersionId, $userId, $appId);
            }
        }
    }

    private function addDurHasDatasetVersion(int $durId, array $dataset, int $datasetVersionId, ?int $userId = null, ?int $appId = null)
    {
        try {

            $searchArray = [
                'dur_id' => $durId,
                'dataset_version_id' => $datasetVersionId,
            ];

            $arrCreate = [
                'dur_id' => $durId,
                'dataset_version_id' => $datasetVersionId,
                'deleted_at' => null,
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

            return DurHasDatasetVersion::withTrashed()->updateOrCreate($searchArray, $arrCreate);

        } catch (Exception $e) {
            throw new Exception("addDurHasDatasetVersion :: " . $e->getMessage());
        }
    }


    private function checkInDurHasDatasetVersion(int $durId, int $datasetVersionId)
    {
        try {
            return DurHasDatasetVersion::where([
                'dur_id' => $durId,
                'dataset_version_id' => $datasetVersionId,
            ])->first();
        } catch (Exception $e) {
            throw new Exception("checkInDurHasDatasetVersion :: " . $e->getMessage());
        }
    }
    private function deleteDurHasDatasetVersion(int $durId, int $datasetVersionId)
    {
        try {
            return DurHasDatasetVersion::where([
                'dur_id' => $durId,
                'dataset_version_id' => $datasetVersionId,
            ])->delete();
        } catch (Exception $e) {
            throw new Exception("deleteDurHasDatasetVersion :: " . $e->getMessage());
        }
    }

    // publications
    private function checkPublications(int $durId, array $inPublications, ?int $userId = null, ?int $appId = null)
    {
        $pubs = DurHasPublication::where(['publication_id' => $durId])->get();
        foreach ($pubs as $p) {
            if (!in_array($p->publication_id, $this->extractInputIdToArray($inPublications))) {
                $this->deleteDurHasPublications($durId, $p->publication_id);
            }
        }

        foreach ($inPublications as $publication) {
            $checking = $this->checkInDurHasPublications($durId, (int) $publication['id']);

            if (!$checking) {
                $this->addDurHasPublication($durId, $publication, $userId, $appId);
            }
        }
    }

    private function addDurHasPublication(int $durId, array $publication, ?int $userId = null, ?int $appId = null)
    {
        try {
            $arrCreate = [
                'dur_id' => $durId,
                'publication_id' => $publication['id'],
            ];

            if (array_key_exists('user_id', $publication)) {
                $arrCreate['user_id'] = (int) $publication['user_id'];
            } elseif ($userId) {
                $arrCreate['user_id'] = $userId;
            }

            if (array_key_exists('reason', $publication)) {
                $arrCreate['reason'] = $publication['reason'];
            }

            if (array_key_exists('updated_at', $publication)) { // special for migration
                $arrCreate['created_at'] = $publication['updated_at'];
                $arrCreate['updated_at'] = $publication['updated_at'];
            }

            if ($appId) {
                $arrCreate['application_id'] = $appId;
            }

            return DurHasPublication::updateOrCreate(
                $arrCreate,
                [
                    'dur_id' => $durId,
                    'publication_id' => $publication['id'],
                ]
            );
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('addDurHasPublication :: ' . $e->getMessage());
        }
    }

    private function checkInDurHasPublications(int $durId, int $publicationId)
    {
        try {
            return DurHasPublication::where([
                'dur_id' => $durId,
                'publication_id' => $publicationId,
            ])->first();
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('checkInDurHasPublications :: ' . $e->getMessage());
        }
    }

    private function deleteDurHasPublications(int $durId, int $publicationId)
    {
        try {
            return DurHasPublication::where([
                'dur_id' => $durId,
                'publication_id' => $publicationId,
            ])->delete();
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('deleteDurHasPublications :: ' . $e->getMessage());
        }
    }

    // keywords
    private function checkKeywords(int $durId, array $inKeywords)
    {
        $kws = DurHasKeyword::where('dur_id', $durId)->get();

        foreach ($kws as $kw) {
            $kwId = $kw->keyword_id;
            $checkKeyword = Keyword::where('id', $kwId)->first();

            if (!$checkKeyword) {
                $this->deleteDurHasKeywords($kwId);
                continue;
            }

            if (in_array($checkKeyword->name, $inKeywords)) {
                continue;
            }

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
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('addKeywordDur :: ' . $e->getMessage());
        }
    }

    private function updateOrCreateKeyword($keyword)
    {
        try {
            return Keyword::updateOrCreate([
                'name' => $keyword,
            ], [
                'name' => $keyword,
                'enabled' => 1,
            ]);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('createUpdateKeyword :: ' . $e->getMessage());
        }
    }

    private function deleteDurHasKeywords($keywordId)
    {
        try {
            return DurHasKeyword::where(['keyword_id' => $keywordId])->delete();
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('deleteKeywordDur :: ' . $e->getMessage());
        }
    }

    // tools
    private function checkTools(int $durId, array $inTools)
    {
        $tools = DurHasTool::where('dur_id', $durId)->get();

        foreach ($tools as $tool) {
            $toolId = $tool->tool_id;
            $checkTool = Tool::where('id', $toolId)->first();

            if (!$checkTool) {
                $this->deleteDurHasTools($durId, $toolId);
                continue;
            }

            if (in_array($checkTool->id, $inTools)) {
                continue;
            }

            if (!in_array($checkTool->id, $inTools)) {
                $this->deleteDurHasTools($durId, $toolId);
            }
        }

        foreach ($inTools as $inTool) {
            $this->updateOrCreateDurHasTools($durId, $inTool);
        }
    }

    private function updateOrCreateDurHasTools(int $durId, int $toolId)
    {
        try {
            return DurHasTool::firstOrCreate(
                [
                'dur_id' => $durId,
                'tool_id' => $toolId,
            ],
                [
                'dur_id' => $durId,
                'tool_id' => $toolId,
            ]
            );
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('updateOrCreateDurHasTools :: ' . $e->getMessage());
        }
    }

    private function deleteDurHasTools(int $dId, int $tId)
    {
        try {
            return DurHasTool::where([
                'dur_id' => $dId,
                'tool_id' => $tId,
            ])->delete();
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('deleteDurHasTools :: ' . $e->getMessage());
        }
    }
    private function extractInputIdToArray(array $input): array
    {
        $response = [];
        foreach ($input as $value) {
            $response[] = $value['id'];
        }

        return $response;
    }

    /**
     * Map the input string to the index of one of the standard mapped sector names.
     *
     * Return null if not found.
     * @return ?int
     */
    private function mapOrganisationSector(string $organisationSector): ?int
    {
        $sector = strtolower($organisationSector);
        $categories = Sector::all();

        // Look up mapped sector, with default to null
        $category = Config::get('sectors.' . $sector, null);

        return (!is_null($category)) ? $categories->where('name', $category)->first()['id'] : null;
    }

    /**
     * Find dataset title associated with a given dataset id.
     *
     * @param int $id The dataset id
     *
     * @return string
     */
    private function getDatasetTitle(int $id): string
    {
        $metadata = Dataset::where(['id' => $id])
            ->first()
            ->latestVersion()
            ->metadata;
        $title = $metadata['metadata']['summary']['shortTitle'];
        return $title;
    }

    //Get Durs
    private function getDurById(int $durId)
    {
        $dur = Dur::where(['id' => $durId])
            ->with([
                'keywords',
                'publications',
                'tools',
                'userDatasets' => function ($query) {
                    $query->distinct('id');
                },
                'userPublications' => function ($query) {
                    $query->distinct('id');
                },
                'applicationDatasets' => function ($query) {
                    $query->distinct('id');
                },
                'applicationPublications' => function ($query) {
                    $query->distinct('id');
                },
                'user',
                'team',
            ])->first();

        $userDatasets = $dur->userDatasets;
        $userPublications = $dur->userPublications;
        $users = $userDatasets->merge($userPublications)->unique('id');
        $dur->setRelation('users', $users);

        $applicationDatasets = $dur->applicationDatasets;
        $applicationPublications = $dur->applicationPublications;
        $applications = $applicationDatasets->merge($applicationPublications)->unique('id');
        $dur->setRelation('applications', $applications);

        unset(
            $users,
            $userDatasets,
            $userPublications,
            $applications,
            $applicationDatasets,
            $applicationPublications,
            $dur->userDatasets,
            $dur->userPublications,
            $dur->applicationDatasets,
            $dur->applicationPublications
        );

        // Fetch datasets using the accessor
        $datasets = $dur->allDatasets  ?? [];

        foreach ($datasets as &$dataset) {
            $dataset['shortTitle'] = $this->getDatasetTitle($dataset['id']);
        }

        // Update the relationship with the modified datasets
        $dur->setAttribute('datasets', $datasets);

        return $dur;
    }
}
