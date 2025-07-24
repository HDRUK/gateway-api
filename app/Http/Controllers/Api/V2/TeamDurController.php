<?php

namespace App\Http\Controllers\Api\V2;

use Config;
use Auditor;
use Exception;
use App\Models\Dur;
use App\Models\Tool;
use App\Models\Dataset;
use App\Models\Keyword;
use App\Models\DurHasTool;
use Illuminate\Http\Request;
use App\Models\DurHasKeyword;
use App\Models\DatasetVersion;
use App\Models\CollectionHasDur;
use App\Http\Traits\CheckAccess;
use App\Http\Traits\DurV2Helpers;
use Illuminate\Http\JsonResponse;
use App\Models\DurHasPublication;
use App\Models\DurHasDatasetVersion;
use App\Http\Controllers\Controller;
use App\Exceptions\NotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Http\Requests\V2\Dur\CreateDurByTeam;
use App\Http\Requests\V2\Dur\GetDurByTeamAndId;
use App\Http\Requests\V2\Dur\EditDurByTeamAndId;
use App\Http\Requests\V2\Dur\DeleteDurByTeamAndId;
use App\Http\Requests\V2\Dur\UpdateDurByTeamAndId;
use App\Http\Requests\V2\Dur\GetDurByTeamAndStatus;
use App\Http\Requests\V2\Dur\GetDurCountByTeamAndStatus;
use App\Http\Traits\MapOrganisationSector;
use App\Http\Traits\RequestTransformation;

class TeamDurController extends Controller
{
    use RequestTransformation;
    use MapOrganisationSector;
    use CheckAccess;
    use DurV2Helpers;

    /**
     * @OA\Get(
     *    path="/api/v2/teams/{teamId}/dur/status/{status}",
     *    operationId="fetch_all_team_dur_status",
     *    tags={"Data Use Registers"},
     *    summary="TeamDurController@indexStatus",
     *    description="Returns a list of dur owned by this team with given status",
     *    @OA\Parameter(
     *       name="status",
     *         in="path",
     *         description="Status of the DUR (active, draft, or archived). Defaults to active if not provided.",
     *         required=false,
     *         @OA\Schema( type="string", enum={"active", "draft", "archived"}, default="active" )
     *     ),
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
     *       description="Filter dur by project title"
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
     *    @OA\Parameter(
     *       name="with_related",
     *       in="query",
     *       description="Show related entities",
     *       required=false,
     *       @OA\Schema(
     *           type="boolean"
     *       )
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
     *    ),
     *    @OA\Response(
     *        response=404,
     *        description="Not Found"
     *    )
     * )
     *
     * @param  GetDurByTeamAndStatus  $request
     * @param  int  $teamId
     * @param  string|null  $status
     * @return JsonResponse
     * )
     */
    public function indexStatus(GetDurByTeamAndStatus $request, int $teamId, ?string $status = 'active'): JsonResponse
    {
        $input = $request->all();

        $this->checkAccess($input, $teamId, null, 'team', $request->header());

        try {
            $projectTitle = $request->query('project_title', null);
            $perPage = request('per_page', Config::get('constants.per_page'));
            $withRelated = $request->boolean('with_related', true);

            $durs = Dur::where([
                'team_id' => $teamId,
                'status' => strtoupper($status),
            ])
            ->when($projectTitle, function ($query) use ($projectTitle) {
                return $query->where('project_title', 'like', '%'. $projectTitle .'%');
            })
            ->when(
                $withRelated,
                fn ($query) => $query
                ->with([
                    'publications',
                    'tools',
                    'keywords',
                    // SC: I can't get these fields to work properly when applying a status=ACTIVE condition to the underlying entity.
                    // I don't think the FE ever uses this information, so I'm disabling it until it ever is required again.
                    // 'userDatasets' => function ($query) {
                    //     $query->distinct('id');
                    // },
                    // 'userPublications' => function ($query) {
                    //     $query->distinct('id');
                    // },
                    // 'applicationDatasets' => function ($query) {
                    //     $query->distinct('id');
                    // },
                    // 'applicationPublications' => function ($query) {
                    //     $query->distinct('id');
                    // },
                    'user',
                    'team',
                    'application',
                ])
            )
            ->applySorting()
            ->paginate((int) $perPage, ['*'], 'page');

            $durs->getCollection()->transform(function ($dur) {
                $datasets = $dur->allDatasets  ?? [];

                foreach ($datasets as &$dataset) {
                    $dataset['shortTitle'] = $this->getDatasetTitle($dataset['id']);
                }
                // Update the relationship with the modified datasets
                $dur->setAttribute('datasets', $datasets);

                unset($datasets);

                return $dur;
            });

            // SC: disabling for now (see comment above)
            // $durs->getCollection()->transform(function ($dur) {
            //     $userDatasets = $dur->userDatasets;
            //     $userPublications = $dur->userPublications;
            //     $dur->setAttribute('datasets', $dur->allDatasets  ?? []);
            //     $applicationDatasets = $dur->applicationDatasets;
            //     $applicationPublications = $dur->applicationPublications;
            //     $users = $userDatasets->merge($userPublications)->unique('id');
            //     $applications = $applicationDatasets->merge($applicationPublications)->unique('id');
            //     $dur->setRelation('users', $users);
            //     $dur->setRelation('applications', $applications);


            //     unset(
            //         $users,
            //         $userDatasets,
            //         $userPublications,
            //         $applications,
            //         $applicationDatasets,
            //         $applicationPublications,
            //         $dur->userDatasets,
            //         $dur->userPublications,
            //         $dur->applicationDatasets,
            //         $dur->applicationPublications
            //     );

            //     return $dur;
            // });

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Team Dur get all by status",
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

    /**
     * @OA\Get(
     *    path="/api/v2/teams/{teamId}/dur/count/{field}",
     *    operationId="count_team_unique_fields_dur_v2",
     *    tags={"Data Use Registers"},
     *    summary="TeamDurController@count",
     *    description="Get team counts for distinct entries of a field in the model",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="teamId",
     *       in="path",
     *       description="team id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="team id",
     *       ),
     *    ),
     *    @OA\Parameter(
     *       name="field",
     *       in="path",
     *       description="name of the field to perform a count on",
     *       required=true,
     *       example="status",
     *       @OA\Schema(
     *          type="string",
     *          description="status field",
     *       ),
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(
     *             property="data",
     *             type="object",
     *          )
     *       )
     *    )
     * )
     */
    public function count(GetDurCountByTeamAndStatus $request, int $teamId, string $field): JsonResponse
    {
        $this->checkAccess($request->all(), $teamId, null, 'team', $request->header());

        try {
            $counts = Dur::where('team_id', $teamId)->applyCount();

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Team Dur count',
            ]);

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
     *    path="/api/v1/teams/{teamId}/dur/{id}",
     *    operationId="fetch_dur_by_team_and_by_id_v2",
     *    tags={"Data Use Registers"},
     *    summary="TeamDurController@show",
     *    description="Get dur by team id and by id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="teamId",
     *       in="path",
     *       description="team id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="team id",
     *       ),
     *    ),
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
     *             @OA\Property(property="data", type="object",
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
     *                @OA\Property(property="applications", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="user", type="array", example="{}", @OA\Items()),
     *                @OA\Property(property="team", type="array", example="{}", @OA\Items()),
     *                @OA\Property(property="status", type="string", enum={"ACTIVE", "DRAFT", "ARCHIVED"}),
     *             ),
     *          ),
     *       ),
     *    ),
     * )
     */
    public function show(GetDurByTeamAndId $request, int $teamId, int $id): JsonResponse
    {
        $input = $request->all();
        $this->checkAccess($input, $teamId, null, 'team', $request->header());

        try {
            $dur = $this->getDurById($id, teamId: $teamId);

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
                'description' => 'Team Dur get ' . $id,
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $dur,
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (NotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
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
     *    path="/api/v2/teams/{teamId}/dur",
     *    operationId="create_dur_by_team_v2",
     *    tags={"Data Use Registers"},
     *    summary="TeamDurController@store",
     *    description="Create a new dur by team v2",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="teamId",
     *       in="path",
     *       description="team id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="team id",
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
    public function store(CreateDurByTeam $request, int $teamId): JsonResponse
    {
        list($userId) = $this->getAccessorUserAndTeam($request);
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $currentUser = isset($jwtUser['id']) ? (int) $jwtUser['id'] : $userId;

        $this->checkAccess($input, $teamId, null, 'team', $request->header());

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
        $array['team_id'] = $teamId;
        $array['user_id'] = $currentUser;

        if (isset($array['organisation_sector'])) {
            $array['sector_id'] = $this->mapOrganisationSector($array['organisation_sector']);
        }

        try {

            $dur = Dur::create($array);
            $durId = $dur->id;

            // link dur with datasets
            $datasets = $input['datasets'] ?? [];
            $this->checkDatasets($durId, $datasets, $currentUser);

            // link dur with publications
            $publications = $input['publications'] ?? [];
            $this->checkPublications($durId, $publications, $currentUser);

            // link dur with keywords
            $keywords = $input['keywords'] ?? [];
            $this->checkKeywords($durId, $keywords);

            // link dur with tools
            $tools = $input['tools'] ?? [];
            $this->checkTools($durId, $tools);

            Auditor::log([
                'user_id' => $currentUser,
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Team Dur ' . $durId . ' created',
            ]);

            return response()->json([
                'message' => 'created',
                'data' => $durId,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => $currentUser,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *    path="/api/v2/teams/{teamId}/dur/{id}",
     *    operationId="update_dur_v2_by_team_id",
     *    tags={"Data Use Registers"},
     *    summary="TeamDurController@update",
     *    description="Update a dur by team and id v2",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="teamId",
     *       in="path",
     *       description="team id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="team id",
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
    public function update(UpdateDurByTeamAndId $request, int $teamId, int $id): JsonResponse
    {
        list($userId) = $this->getAccessorUserAndTeam($request);
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $currentUser = isset($jwtUser['id']) ? (int) $jwtUser['id'] : $userId;
        $initDur = Dur::where('id', $id)->first();
        if (!$initDur) {
            throw new NotFoundException();
        }
        $this->checkAccess($input, $initDur->team_id, null, 'team', $request->header());
        if ($initDur->team_id !== $teamId) {
            throw new UnauthorizedException();
        }

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
            $array = $this->checkUpdateArray($input, $arrayKeys);

            if ($array['organisation_sector']) {
                $array['sector_id'] = $this->mapOrganisationSector($array['organisation_sector']);
            }

            // if not supplied, set fields which have a NOT NULL constraint to their defaults
            if (is_null($array['manual_upload'])) {
                $array['manual_upload'] = 1;
            }
            if (is_null($array['enabled'])) {
                $array['enabled'] = 1;
            }
            if (is_null($array['counter'])) {
                $array['counter'] = 0;
            }
            if (is_null($array['status'])) {
                $array['status'] = Dur::STATUS_DRAFT;
            }

            Dur::where(['id' => $id, 'team_id' => $teamId])->first()->update($array);

            // link/unlink dur with datasets
            $datasets = $input['datasets'] ?? [];
            $this->checkDatasets($id, $datasets, $currentUser);

            // link/unlink dur with publications
            $publications = $input['publications'] ?? [];
            $this->checkPublications($id, $publications, $currentUser);

            // link/unlink dur with keywords
            $keywords = $input['keywords'] ?? [];
            $this->checkKeywords($id, $keywords);

            // link/unlink dur with tools
            $tools = $input['tools'] ?? [];
            $this->checkTools($id, $tools);

            Auditor::log([
                'user_id' => $currentUser,
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Team Dur ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $this->getDurById($id, teamId: $teamId),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => $currentUser,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *    path="/api/v2/teams/{teamId}/dur/{id}",
     *    operationId="edit_durs_v2_by_team_id",
     *    tags={"Data Use Registers"},
     *    summary="TeamDurController@edit",
     *    description="Edit a dur by team v2",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="teamId",
     *       in="path",
     *       description="team id",
     *       required=true,
     *       example="1",
     *       @OA\Schema( type="integer", description="team id" ),
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
    public function edit(EditDurByTeamAndId $request, int $teamId, int $id): JsonResponse
    {
        list($userId) = $this->getAccessorUserAndTeam($request);
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $currentUser = isset($jwtUser['id']) ? (int) $jwtUser['id'] : $userId;
        $initDur = Dur::where('id', $id)->first();
        if (!$initDur) {
            throw new NotFoundException();
        }
        $this->checkAccess($input, $initDur->team_id, null, 'team', $request->header());
        if ($initDur->team_id !== $teamId) {
            throw new UnauthorizedException();
        }

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

            if (array_key_exists('organisation_sector', $array)) {
                $array['sector_id'] = $this->mapOrganisationSector($array['organisation_sector']);
            }

            Dur::where(['id' => $id, 'team_id' => $teamId])->first()->update($array);

            // link/unlink dur with datasets
            if (array_key_exists('datasets', $input)) {
                $this->checkDatasets($id, $input['datasets'], $currentUser);
            }
            // link/unlink dur with publications
            if (array_key_exists('publications', $input)) {
                $this->checkPublications($id, $input['publications'], $currentUser);
            }
            // link/unlink dur with keywords
            if (array_key_exists('keywords', $input)) {
                $this->checkKeywords($id, $input['keywords']);
            }
            // link/unlink dur with tools
            if (array_key_exists('tools', $input)) {
                $this->checkTools($id, $input['tools']);
            }

            Auditor::log([
                'user_id' => $currentUser,
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Team Dur ' . $id . ' edited',
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $this->getDurById($id, teamId: $teamId),
            ], Config::get('statuscodes.STATUS_OK.code'));

        } catch (Exception $e) {
            Auditor::log([
                'user_id' => $currentUser,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *    path="/api/v2/teams/{teamId}/dur/{id}",
     *    operationId="delete_durs_v2_by_team_id",
     *    tags={"Data Use Registers"},
     *    summary="TeamDurController@destroy",
     *    description="Delete a dur by team and id v2",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="teamId",
     *       in="path",
     *       description="team id",
     *       required=true,
     *       example="1",
     *       @OA\Schema( type="integer", description="team id" ),
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
    public function destroy(DeleteDurByTeamAndId $request, int $teamId, int $id): JsonResponse
    {
        list($userId) = $this->getAccessorUserAndTeam($request);
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $currentUser = isset($jwtUser['id']) ? (int) $jwtUser['id'] : $userId;

        $initDur = Dur::where(['id' => $id])->first();
        if (!$initDur) {
            throw new NotFoundException();
        }
        $this->checkAccess($input, $initDur->team_id, null, 'team', $request->header());
        if ($initDur->team_id !== $teamId) {
            throw new UnauthorizedException();
        }

        try {
            DurHasDatasetVersion::where(['dur_id' => $id])->delete();
            DurHasKeyword::where(['dur_id' => $id])->delete();
            DurHasPublication::where(['dur_id' => $id])->delete();
            DurHasTool::where(['dur_id' => $id])->delete();
            CollectionHasDur::where(['dur_id' => $id])->delete();
            Dur::where(['id' => $id])->first()->delete();

            Auditor::log([
                'user_id' => $currentUser,
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Team Dur ' . $id . ' deleted',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => $currentUser,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    // datasets
    private function checkDatasets(int $durId, array $inDatasets, ?int $userId = null)
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

    private function addDurHasDatasetVersion(int $durId, array $dataset, int $datasetVersionId, ?int $userId = null)
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

            throw new Exception('addDurHasDatasetVersion :: ' . $e->getMessage());
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
    private function checkPublications(int $durId, array $inPublications, ?int $userId = null)
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

    private function addDurHasPublication(int $durId, array $publication, ?int $userId = null)
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
}
