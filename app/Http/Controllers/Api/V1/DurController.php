<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;
use App\Models\Dur;
use App\Models\Tool;
use App\Models\Dataset;
use App\Models\Keyword;
use App\Models\Collection;
use App\Models\DurHasTool;
use Illuminate\Http\Request;
use App\Models\DurHasKeyword;
use App\Models\DatasetVersion;
use Illuminate\Support\Carbon;
use App\Http\Traits\CheckAccess;
use App\Http\Requests\Dur\GetDur;
use App\Models\DurHasPublication;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Dur\EditDur;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dur\CreateDur;
use App\Http\Requests\Dur\DeleteDur;
use App\Http\Requests\Dur\UpdateDur;
use App\Http\Requests\Dur\UploadDur;
use App\Models\DurHasDatasetVersion;

use App\Exceptions\NotFoundException;
use Illuminate\Support\Facades\Storage;
use App\Http\Traits\MapOrganisationSector;
use App\Http\Traits\RequestTransformation;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DurController extends Controller
{
    use RequestTransformation;
    use MapOrganisationSector;
    use CheckAccess;

    /**
     * @OA\Get(
     *    path="/api/v1/dur",
     *    operationId="fetch_all_dur",
     *    tags={"Data Use Registers"},
     *    summary="DurController@index",
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
     *       name="project_title",
     *       in="query",
     *       required=false,
     *       @OA\Schema(type="string"),
     *       description="Filter tools by project title"
     *    ),
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
     *                @OA\Property(property="publications", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="tools", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="users", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="user", type="array", example="{}", @OA\Items()),
     *                @OA\Property(property="team", type="array", example="{}", @OA\Items()),
     *                @OA\Property(property="application", type="string", example=""),
     *                @OA\Property(property="applications", type="string", example=""),
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
        $input = $request->all();

        try {
            $sort = [];
            $sortArray = $request->has('sort') ? explode(',', $request->query('sort', '')) : [];
            foreach ($sortArray as $item) {
                $tmp = explode(":", $item);
                $sort[$tmp[0]] = array_key_exists('1', $tmp) ? $tmp[1] : 'asc';
            }
            if (!array_key_exists('updated_at', $sort)) {
                $sort['updated_at'] = 'desc';
            }

            $mongoId = $request->query('mongo_id', null);
            $projectTitle = $request->query('project_title', null);
            $teamId = $request->query('team_id', null);
            $projectId = $request->query('project_id', null);
            $filterStatus = $request->query('status', null);
            $perPage = request('perPage', Config::get('constants.per_page'));
            $withRelated = $request->boolean('with_related', true);
            $durs = Dur::when($mongoId, function ($query) use ($mongoId) {
                return $query->where('mongo_id', '=', $mongoId);
            })->when(
                $request->has('withTrashed') || $filterStatus === 'ARCHIVED',
                function ($query) {
                    return $query->withTrashed();
                }
            )->when($projectTitle, function ($query) use ($projectTitle) {
                return $query->where('project_title', 'like', '%'. $projectTitle .'%');
            })->when($teamId, function ($query) use ($teamId) {
                return $query->where('team_id', '=', $teamId);
            })->when($projectId, function ($query) use ($projectId) {
                return $query->where('project_id_text', '=', $projectId);
            })->when(
                $filterStatus,
                function ($query) use ($filterStatus) {
                    return $query->where('status', '=', $filterStatus);
                }
            )->when(
                $withRelated,
                fn ($query) => $query
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
                ])
            );

            foreach ($sort as $key => $value) {
                $durs->orderBy('dur.' . $key, strtoupper($value));
            }

            $durs = $durs->paginate((int) $perPage, ['*'], 'page')
                ->through(function ($dur) {
                    if ($dur->datasets) {
                        $dur->datasets = $dur->datasets->map(function ($dataset) {
                            $dataset->shortTitle = $this->getDatasetTitle($dataset->id);
                            return $dataset;
                        });
                    }
                    return $dur;
                });

            $durs->getCollection()->transform(function ($dur) {
                $userDatasets = $dur->userDatasets;
                $userPublications = $dur->userPublications;
                $dur->setAttribute('datasets', $dur->allDatasets  ?? []);
                $applicationDatasets = $dur->applicationDatasets;
                $applicationPublications = $dur->applicationPublications;
                $users = $userDatasets->merge($userPublications)->unique('id');
                $applications = $applicationDatasets->merge($applicationPublications)->unique('id');
                $dur->setRelation('users', $users);
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
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Dur get all",
            ]);

            return response()->json(
                $durs
            );
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    public function count(Request $request, string $field): JsonResponse
    {
        try {
            $teamId = $request->query('team_id', null);
            $counts = Dur::when($teamId, function ($query) use ($teamId) {
                return $query->where('team_id', '=', $teamId);
            })->withTrashed()
                ->select($field)
                ->get()
                ->groupBy($field)
                ->map->count();

            return response()->json([
                'data' => $counts
            ]);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
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
     *                   @OA\Property(property="publications", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="tools", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="keywords", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="users", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="applications", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="user", type="array", example="{}", @OA\Items()),
     *                   @OA\Property(property="team", type="array", example="{}", @OA\Items()),
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
        try {
            $dur = $this->getDurById($id);

            if (!empty($dur['user'])) {
                $dur['user'] = array_intersect_key($dur['user'], array_flip(['id', 'firstname', 'lastname']));
            }

            if (!empty($dur['users']) && is_array($dur['users'])) {
                foreach ($dur['users'] as &$user) {
                    $user = array_intersect_key($user, array_flip(['id', 'firstname', 'lastname']));
                }
                unset($user);
            }

            if (!empty($dur['team'])) {
                $dur['team'] = array_intersect_key($dur['team'], array_flip(['id', 'name', 'team_logo', 'member_of', 'contact_point']));
            }

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Dur get ' . $id,
            ]);

            return response()->json([
                'message' => 'success',
                'data' => [$dur],
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

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
     *             @OA\Property(property="publications", type="array", example="[]", @OA\Items()),
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
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

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
            'status',
            'project_start_date',
            'project_end_date',
            'latest_approval_date',
        ];
        $array = $this->checkEditArray($input, $arrayKeys);
        $array['team_id'] = array_key_exists('team_id', $input) ? $input['team_id'] : null;

        if (!array_key_exists('team_id', $array)) {
            throw new NotFoundException("Team Id not found in request.");
        }

        $this->checkAccess($input, $array['team_id'], null, 'team');

        if (isset($array['organisation_sector'])) {
            $array['sector_id'] = $this->mapOrganisationSector($array['organisation_sector']);
        }

        try {

            $dur = Dur::create($array);
            $durId = $dur->id;

            // link/unlink dur with datasets
            $datasets = array_key_exists('datasets', $input) ? $input['datasets'] : [];
            $this->checkDatasets($durId, $datasets, (int)$jwtUser['id']);

            // link/unlink dur with publications
            $publications = array_key_exists('publications', $input) ? $input['publications'] : [];
            $this->checkPublications($durId, $publications, (int)$jwtUser['id']);

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
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Dur ' . $durId . ' created',
            ]);

            return response()->json([
                'message' => 'created',
                'data' => $durId,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

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
     *             @OA\Property(property="publications", type="array", example="[]", @OA\Items()),
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
     *                   @OA\Property(property="technicalSummary", type="string", example="Similique officia dolor nam. ..."),
     *                   @OA\Property(property="enabled", type="boolean", example="1"),
     *                   @OA\Property(property="last_activity", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="counter", type="integer", example="34319"),
     *                   @OA\Property(property="mongo_object_id", type="string", example="5f32a7d53b1d85c427e97c01"),
     *                   @OA\Property(property="mongo_id", type="string", example="38873389090594430"),
     *                   @OA\Property(property="datasets", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="publications", type="array", example="[]", @OA\Items()),
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
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $initDur = Dur::withTrashed()->where('id', $id)->first();
        $this->checkAccess($input, $initDur->team_id, null, 'team');

        try {
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
                'status',
                'project_start_date',
                'project_end_date',
                'latest_approval_date',
            ];
            $array = $this->checkEditArray($input, $arrayKeys);

            if ($initDur['status'] === Dur::STATUS_ARCHIVED && !array_key_exists('status', $input)) {
                throw new Exception('Cannot update current data use register! Status already "ARCHIVED"');
            }

            if (array_key_exists('organisation_sector', $array)) {
                $array['sector_id'] = $this->mapOrganisationSector($array['organisation_sector']);
            }

            Dur::where('id', $id)->first()->update($array);

            // link/unlink dur with datasets
            $datasets = array_key_exists('datasets', $input) ? $input['datasets'] : [];
            $this->checkDatasets($id, $datasets, (int)$jwtUser['id']);

            // link/unlink dur with publications
            $publications = array_key_exists('publications', $input) ? $input['publications'] : [];
            $this->checkPublications($id, $publications, (int)$jwtUser['id']);

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

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Dur ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $this->getDurById($id),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

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
     *       name="unarchive",
     *       in="query",
     *       description="Unarchive a dur",
     *       @OA\Schema(
     *          type="string",
     *          description="instruction to unarchive dur",
     *       ),
     *    ),
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
     *             @OA\Property(property="publications", type="array", example="[]", @OA\Items()),
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
     *                   @OA\Property(property="technicalSummary", type="string", example="Similique officia dolor nam. ..."),
     *                   @OA\Property(property="enabled", type="boolean", example="1"),
     *                   @OA\Property(property="last_activity", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="counter", type="integer", example="34319"),
     *                   @OA\Property(property="mongo_object_id", type="string", example="5f32a7d53b1d85c427e97c01"),
     *                   @OA\Property(property="mongo_id", type="string", example="38873389090594430"),
     *                   @OA\Property(property="datasets", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="publications", type="array", example="[]", @OA\Items()),
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
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $initDur = Dur::withTrashed()->where('id', $id)->first();
        $this->checkAccess($input, $initDur->team_id, null, 'team');

        try {
            if ($request->has('unarchive')) {
                $durModel = Dur::withTrashed()
                    ->find($id);
                if ($request['status'] !== Dur::STATUS_ARCHIVED) {
                    if (in_array($request['status'], [
                        Dur::STATUS_ACTIVE, Dur::STATUS_DRAFT
                    ])) {
                        $durModel->status = $request['status'];
                        $durModel->deleted_at = null;
                        $durModel->save();

                        // TODO: need to consider how we re-link datasets, publications etc.
                        // Currently, the checkPublications() etc do not consider the case where
                        // we want to restore an existing soft-deleted DurHasX.

                        Auditor::log([
                            'user_id' => (int) $jwtUser['id'],
                            'action_type' => 'UPDATE',
                            'action_name' => class_basename($this) . '@' . __FUNCTION__,
                            'description' => 'Dur ' . $id . ' unarchived and marked as ' . strtoupper($request['status']),
                        ]);
                    }
                }

                return response()->json([
                    'message' => 'success',
                    'data' => $this->getDurById($id),
                ], Config::get('statuscodes.STATUS_OK.code'));
            } else {
                $initDur = Dur::withTrashed()->where('id', $id)->first();

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
                    'status',
                    'project_start_date',
                    'project_end_date',
                    'latest_approval_date',
                ];
                $array = $this->checkEditArray($input, $arrayKeys);

                if ($initDur['status'] === Dur::STATUS_ARCHIVED && !array_key_exists('status', $input)) {
                    throw new Exception('Cannot update current data use register! Status already "ARCHIVED"');
                }

                if (array_key_exists('organisation_sector', $array)) {
                    $array['sector_id'] = $this->mapOrganisationSector($array['organisation_sector']);
                }

                Dur::where('id', $id)->update($array);

                // link/unlink dur with datasets
                $datasets = array_key_exists('datasets', $input) ? $input['datasets'] : [];
                $this->checkDatasets($id, $datasets, (int)$jwtUser['id']);

                // link/unlink dur with publications
                $publications = array_key_exists('publications', $input) ? $input['publications'] : [];
                $this->checkPublications($id, $publications, (int)$jwtUser['id']);

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

                Auditor::log([
                    'user_id' => (int)$jwtUser['id'],
                    'action_type' => 'UPDATE',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'Dur ' . $id . ' updated',
                ]);

                return response()->json([
                    'message' => 'success',
                    'data' => $this->getDurById($id),
                ], Config::get('statuscodes.STATUS_OK.code'));
            }
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

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
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $initDur = Dur::withTrashed()->where('id', $id)->first();
        $this->checkAccess($input, $initDur->team_id, null, 'team');

        try {
            DurHasDatasetVersion::where(['dur_id' => $id])->delete();
            DurHasKeyword::where(['dur_id' => $id])->delete();
            DurHasPublication::where(['dur_id' => $id])->delete();
            DurHasTool::where(['dur_id' => $id])->delete();
            $dur = Dur::where(['id' => $id])->first();
            $dur->deleted_at = Carbon::now();
            $dur->status = Dur::STATUS_ARCHIVED;
            $dur->save();

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Dur ' . $id . ' deleted',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *    path="/api/v1/dur/export",
     *    operationId="export_dur",
     *    tags={"Datasets"},
     *    summary="DurController@export",
     *    description="Export CSV Of All Dur's",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="team_id",
     *       in="query",
     *       description="team id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="team id",
     *       ),
     *    ),
     *    @OA\Parameter(
     *       name="dur_id",
     *       in="query",
     *       description="dur id",
     *       required=false,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="dur id",
     *       ),
     *    ),
     *    @OA\Response(
     *       response=200,
     *       description="CSV file",
     *       @OA\MediaType(
     *          mediaType="text/csv",
     *          @OA\Schema(
     *             type="string"
     *          )
     *       )
     *    ),
     *    @OA\Response(
     *       response=401,
     *       description="Unauthorized",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="unauthorized")
     *       )
     *    )
     * )
     */
    public function export(Request $request): StreamedResponse
    {
        try {
            Config::set('profiling.profiler_active', false);

            $teamId = $request->query('team_id', null);
            $durId = $request->query('dur_id', null);
            $durs = Dur::when($teamId, function ($query) use ($teamId) {
                return $query->where('team_id', '=', $teamId);
            })->when($durId, function ($query) use ($durId) {
                return $query->where('id', '=', $durId);
            })->get();

            // callback function that writes to php://output
            $response = new StreamedResponse(
                function () use ($durs) {

                    // Open output stream
                    $handle = fopen('php://output', 'w');

                    // Call the model for specific headings to include
                    $headerRow = Dur::exportHeadings();

                    // Add CSV headers
                    fputcsv($handle, $headerRow);

                    foreach ($durs as $rowDetails) {
                        $fieldNames = $rowDetails->getFillable();
                        $dataRow = [];

                        foreach ($fieldNames as $name) {
                            switch (gettype($rowDetails->{$name})) {
                                case 'array':
                                    // For arrays, join elements and produce a single string
                                    $dataRow[] = implode('|', $rowDetails->{$name});
                                    break;
                                default:
                                    // Otherwise just ensure we replace nulls with an empty string
                                    if ($rowDetails->{$name} !== null) {
                                        $dataRow[] = $rowDetails->{$name};
                                    } else {
                                        $dataRow[] = '';
                                    }
                                    break;
                            }
                        }
                        fputcsv($handle, $dataRow);
                    }

                    // Close the output stream
                    fclose($handle);
                }
            );

            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment;filename="Datasets.csv"');
            $response->headers->set('Cache-Control', 'max-age=0');

            return $response;
        } catch (Exception $e) {
            dd($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *    path="/api/v1/dur/upload",
     *    operationId="upload_dur",
     *    tags={"Data Use Registers"},
     *    summary="DurController@upload",
     *    description="Create a new dur with upload data",
     *    security={{"bearerAuth":{}}},
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="team_id", type="integer", example=1),
     *             @OA\Property(property="project_title", type="string", example=""),
     *             @OA\Property(property="project_id_text", type="string", example=""),
     *             @OA\Property(property="organisation_name", type="string", example=""),
     *             @OA\Property(property="organisation_id", type="string", example=""),
     *             @OA\Property(property="organisation_sector", type="string", example=""),
     *             @OA\Property(property="non_gateway_applicants", type="string", example=""),
     *             @OA\Property(property="applicant_id", type="integer", example=1),
     *             @OA\Property(property="funders_and_sponsors", type="string", example=""),
     *             @OA\Property(property="accredited_researcher_status", type="string", example=""),
     *             @OA\Property(property="sublicence_arrangements", type="string", example=""),
     *             @OA\Property(property="lay_summary", type="string", example=""),
     *             @OA\Property(property="public_benefit_statement", type="string", example=""),
     *             @OA\Property(property="request_category_type", type="string", example=""),
     *             @OA\Property(property="technical_summary", type="string", example=""),
     *             @OA\Property(property="other_approval_committees", type="string", example=""),
     *             @OA\Property(property="project_start_date", type="string", example=""),
     *             @OA\Property(property="project_end_date", type="string", example=""),
     *             @OA\Property(property="latest_approval_date", type="string", example=""),
     *             @OA\Property(property="data_sensitivity_level", type="string", example=""),
     *             @OA\Property(property="legal_basis_for_data_article6", type="string", example=""),
     *             @OA\Property(property="legal_basis_for_data_article9", type="string", example=""),
     *             @OA\Property(property="duty_of_confidentiality", type="string", example=""),
     *             @OA\Property(property="national_data_optout", type="string", example=""),
     *             @OA\Property(property="request_frequency", type="string", example=""),
     *             @OA\Property(property="dataset_linkage_description", type="string", example=""),
     *             @OA\Property(property="confidential_data_description", type="string", example=""),
     *             @OA\Property(property="access_date", type="string", example=""),
     *             @OA\Property(property="access_type", type="string", example=""),
     *             @OA\Property(property="privacy_enhancements", type="string", example=""),
     *             @OA\Property(property="datasets", type="array", example="[]", @OA\Items()),
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
    public function upload(UploadDur $request): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $arrayKeys = [
                'user_id', // similar with application id
                'team_id', // is required if we create a new dur with jwt token
                'project_title', // projectTitle - Project title
                'project_id_text', // projectIdText - Project ID
                'organisation_name', // organisationName - Organisation name*
                'organisation_id', // organisationId - Organisation ID*
                'organisation_sector', // organisationSector - Organisation sector
                'non_gateway_applicants', // Applicant name(s) - I guess is about non_gateway_applicants
                'applicant_id', // Applicant ID - I guess is about user_id
                'funders_and_sponsors', // fundersAndSponsors - Funders/ Sponsors
                'accredited_researcher_status', // accreditedResearcherStatus - DEA accredited researcher?
                'sublicence_arrangements', // sublicenceArrangements - Sub-licence arrangements (if any)?
                'lay_summary', // laySummary - Lay summary
                'public_benefit_statement', // publicBenefitStatement - Public benefit statement
                'request_category_type', // requestCategoryType - Request category type
                'technical_summary', // technicalSummary - Technical summary
                'other_approval_committees', // otherApprovalCommittees - Other approval committees
                'project_start_date', // projectStartDate - Project start date
                'project_end_date', // projectEndDate - Project end date
                'latest_approval_date', // latestApprovalDate - Latest approval date
                'data_sensitivity_level', // dataSensitivityLevel- Data sensitivity level
                'legal_basis_for_data_article6', // legalBasisForDataArticle6 - Legal basis for provision of data under Article 6
                'legal_basis_for_data_article9', // legalBasisForDataArticle9 - Lawful conditions for provision of data under Article 9
                'duty_of_confidentiality', // dutyOfConfidentiality - Common Law Duty of Confidentiality
                'national_data_optout',  // nationalDataOptOut - National data opt-out applied?
                'request_frequency', // requestFrequency - Request frequency
                'dataset_linkage_description', // datasetLinkageDescription - For linked datasets, specify how the linkage will take place
                'confidential_data_description', // confidentialDataDescription - Description of the confidential data being used
                'access_date', // accessDate - Release/Access date
                'access_type', // accessType - Access type
                'privacy_enhancements', // privacyEnhancements - How has data been processed to enhance privacy?
            ];
            $array = $this->checkEditArray($input, $arrayKeys);

            $nonGatewayApplicants = array_key_exists('non_gateway_applicants', $input) ?
                explode('|', $input['non_gateway_applicants']) : [];
            $datasets = array_key_exists('datasets', $input) ? $input['datasets'] : [];

            if (count($nonGatewayApplicants)) {
                $array['non_gateway_applicants'] = $nonGatewayApplicants;
            }

            $dur = Dur::create($array);
            $durId = $dur->id;

            // link/unlink dur with datasets
            $datasets = array_key_exists('datasets', $input) ? $input['datasets'] : [];
            $this->checkDatasets($durId, $datasets, $array['user_id']);

            // link/unlink dur with publications
            $publications = array_key_exists('publications', $input) ? (array) $input['publications'] : [];
            $this->checkPublications($durId, $publications, $array['user_id']);

            // link/unlink dur with keywords
            $keywords = array_key_exists('keywords', $input) ? (array) $input['keywords'] : [];
            $this->checkKeywords($durId, $keywords);

            // link/unlink dur with tools
            $tools = array_key_exists('tools', $input) ? (array) $input['tools'] : [];
            $this->checkTools($durId, $tools);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'UPLOAD',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Dur ' . $durId . ' uploaded',
            ]);

            return response()->json([
                'message' => 'created',
                'data' => $durId,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
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
     * @OA\Get(
     *    path="/api/v1/dur/template",
     *    tags={"Data Use Registers"},
     *    summary="DurController@exportTemplate",
     *    description="Export Dur upload template",
     *    security={{"bearerAuth":{}}},
     *    @OA\Response(
     *       response=200,
     *       description="File download",
     *       @OA\MediaType(
     *          mediaType="text/csv",
     *          @OA\Schema()
     *       )
     *    ),
     *    @OA\Response(
     *       response=401,
     *       description="Unauthorized",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="unauthorized")
     *       ),
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="File Not Found",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="file_not_found")
     *       ),
     *    ),
     * )
     */
    public function exportTemplate(Request $request)
    {
        Config::set('profiling.profiler_active', false);

        try {
            $file = Config::get('mock_data.data_use_upload_template');

            if (!Storage::disk('mock')->exists($file)) {
                return response()->json(['error' => 'File not found.'], 404);
            }

            return Storage::disk('mock')->download($file)->setStatusCode(Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    // datasets
    private function checkDatasets(int $durId, array $inDatasets, int $userId = null)
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
                $this->addDurHasDatasetVersion($durId, $dataset, $datasetVersionId, $userId);
            }
        }
    }

    private function addDurHasDatasetVersion(int $durId, array $dataset, int $datasetVersionId, int $userId = null)
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

            return DurHasDatasetVersion::updateOrCreate($searchArray, $arrCreate);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('addDurHasDataset :: ' . $e->getMessage());
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
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('checkInDurHasDatasetVersion :: ' . $e->getMessage());
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
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('deleteDurHasDatasetVersion :: ' . $e->getMessage());
        }
    }

    // publications
    private function checkPublications(int $durId, array $inPublications, int $userId = null)
    {
        $pubs = DurHasPublication::where(['dur_id' => $durId])->get();
        foreach ($pubs as $p) {
            if (!in_array($p->publication_id, $this->extractInputIdToArray($inPublications))) {
                $this->deleteDurHasPublications($durId, $p->publication_id);
            }
        }

        foreach ($inPublications as $publication) {
            $checking = $this->checkInDurHasPublications($durId, (int)$publication['id']);

            if (!$checking) {
                $this->addDurHasPublication($durId, $publication, $userId);
            }
        }
    }

    private function addDurHasPublication(int $durId, array $publication, int $userId = null)
    {
        try {
            $arrCreate = [
                'dur_id' => $durId,
                'publication_id' => $publication['id'],
            ];

            if (array_key_exists('user_id', $publication)) {
                $arrCreate['user_id'] = (int)$publication['user_id'];
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

        foreach($kws as $kw) {
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

        foreach($tools as $tool) {
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
        return array_map(function ($value) {
            return $value['id'];
        }, $input);
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
                'collections' => function ($query) {
                    $query->where('status', Collection::STATUS_ACTIVE);
                },
            ])->first();

        $userDatasets = $dur->userDatasets;
        $userPublications = $dur->userPublications;
        $users = $userDatasets->merge($userPublications)
            ->unique('id');
        $dur->setRelation('users', $users);

        $applicationDatasets = $dur->applicationDatasets;
        $applicationPublications = $dur->applicationPublications;
        $applications = $applicationDatasets->merge($applicationPublications)
            ->unique('id');
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

        return $dur->toArray();
    }
}
