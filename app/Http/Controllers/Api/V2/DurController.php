<?php

namespace App\Http\Controllers\Api\V2;

use Config;
use Auditor;
use Exception;
use App\Models\Dur;
use App\Models\Dataset;
use Illuminate\Http\Request;
use App\Http\Traits\CheckAccess;
use App\Http\Traits\DurV2Helpers;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\V2\Dur\GetDur;
use App\Http\Controllers\Controller;
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
    use DurV2Helpers;

    /**
     * @OA\Get(
     *    path="/api/v2/dur",
     *    operationId="fetch_all_dur_v2",
     *    tags={"Data Use Registers"},
     *    summary="DurController@indexActive",
     *    description="Returns a list of active dur",
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
     *          @OA\Property(property="first_page_url", type="string", example="http:\/\/localhost:8000\/api\/v2\/dur?page=1"),
     *          @OA\Property(property="from", type="integer", example="1"),
     *          @OA\Property(property="last_page", type="integer", example="1"),
     *          @OA\Property(property="last_page_url", type="string", example="http:\/\/localhost:8000\/api\/v2\/dur?page=1"),
     *          @OA\Property(property="links", type="array", example="[]", @OA\Items(type="array", @OA\Items())),
     *          @OA\Property(property="next_page_url", type="string", example="null"),
     *          @OA\Property(property="path", type="string", example="http:\/\/localhost:8000\/api\/v2\/dur"),
     *          @OA\Property(property="per_page", type="integer", example="25"),
     *          @OA\Property(property="prev_page_url", type="string", example="null"),
     *          @OA\Property(property="to", type="integer", example="3"),
     *          @OA\Property(property="total", type="integer", example="3"),
     *       )
     *    )
     * )
     */
    public function indexActive(Request $request): JsonResponse
    {
        try {
            $projectTitle = $request->query('project_title', null);
            $perPage = request('per_page', Config::get('constants.per_page'));
            $withRelated = $request->boolean('with_related', true);

            $durs = Dur::when($projectTitle, function ($query) use ($projectTitle) {
                return $query->where('project_title', 'like', '%'. $projectTitle .'%');
            })->where(
                'status',
                '=',
                'ACTIVE'
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
            )
            ->applySorting()
            ->paginate((int) $perPage, ['*'], 'page')
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
                'description' => "Dur get all active",
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
     *    path="/api/v2/dur/{id}",
     *    operationId="fetch_dur_by_id_v2",
     *    tags={"Data Use Registers"},
     *    summary="DurController@showActive",
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
    public function showActive(GetDur $request, int $id): JsonResponse
    {
        try {
            $dur = $this->getDurById($id, status: 'ACTIVE');

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
     * @OA\Get(
     *    path="/api/v2/dur/export",
     *    operationId="export_dur_v2",
     *    tags={"Data Use Registers"},
     *    summary="DurController@export",
     *    description="Export CSV of one or more DURs",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
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

            $durId = $request->query('id', null);
            $durs = Dur::when($durId, function ($query) use ($durId) {
                return $query->where('id', '=', $durId);
            })
            ->where('status', Dur::STATUS_ACTIVE)
            ->get();

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
            $response->headers->set('Content-Disposition', 'attachment;filename="DataUses.csv"');
            $response->headers->set('Cache-Control', 'max-age=0');

            return $response;
        } catch (Exception $e) {
            dd($e->getMessage());
        }
    }


    /**
     * @OA\Get(
     *    path="/api/v2/dur/template",
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
