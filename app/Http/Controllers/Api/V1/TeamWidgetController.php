<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;
use App\Http\Controllers\Controller;
use App\Models\Widget;
use App\Models\Dataset;
use App\Models\Collection;
use App\Models\Tool;
use App\Models\Dur;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Traits\LoggingContext;

class TeamWidgetController extends Controller
{
    use LoggingContext;


    /**
     * @OA\Get(
     *    path="/api/v1/teams/{teamId}/widgets",
     *    operationId="fetch_all_widgets",
     *    tags={"Widgets"},
     *    summary="WidgetController@get",
     *    description="Get All Widgets",
     *    security={{"bearerAuth":{}}},
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(
     *             property="data",
     *             type="array",
     *             example="[]",
     *             @OA\Items(
     *                type="array",
     *                @OA\Items()
     *             )
     *          )
     *       )
     *    )
     * )
     */
    public function get(Request $request, int $teamId): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $loggingContext = $this->getLoggingContext($request);
        $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        try {
            $widgets = Widget::with(['team:id,name'])
                ->where('team_id', $teamId)
                ->get([
                    'id',
                    'widget_name',
                    'size_width',
                    'size_height',
                    'updated_at',
                    'unit',
                    'team_id'
                ])
                ->map(function ($widget) {
                    return [
                        'id' => $widget->id,
                        'widget_name' => $widget->widget_name,
                        'size_width' => $widget->size_width,
                        'size_height' => $widget->size_height,
                        'updated_at' => $widget->updated_at,
                        'unit' => $widget->unit,
                        'team_id' => $widget->team_id,
                        'team_name' => $widget->team->name,
                    ];
                });

            return response()->json(['data' => $widgets]);

        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            \Log::info($e->getMessage(), $loggingContext);
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *    path="/api/v1/teams/{teamId}/widgets/{id}",
     *    operationId="fetch_widget",
     *    tags={"Widgets"},
     *    summary="WidgetController@retrieve",
     *    description="Get a single Widget",
     *    security={{"bearerAuth":{}}},
     *    @OA\Response(
     *       response=200,
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(
     *             property="data",
     *             type="object",
     *             example={"id": 1, "widget_name": "Example Widget"}
     *          )
     *       )
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="Widget not found",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="not found")
     *       )
     *    )
     * )
     */

    public function retrieve(Request $request, int $teamId, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $loggingContext = $this->getLoggingContext($request);
        $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        try {
            $widget = Widget::where('id', $id)
               ->where('team_id', $teamId)
               ->first();

            if (! $widget) {
                return response()->json(['message' => 'not found'], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
            }

            $transformed = $widget->toArray();

            foreach ([
                'included_datasets',
                'included_data_uses',
                'included_scripts',
                'included_collections',
                'data_custodian_entities_ids',
                'permitted_domains',
            ] as $field) {
                if (!empty($transformed[$field])) {
                    $transformed[$field] = array_filter(
                        array_map('trim', explode(',', $transformed[$field]))
                    );
                } else {
                    $transformed[$field] = [];
                }
            }

            return response()->json(['data' => $transformed], Config::get('statuscodes.STATUS_OK.code'));


        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            \Log::info($e->getMessage(), $loggingContext);

            throw new Exception($e->getMessage());
        }
    }


    /**
     * @OA\Get(
     *      path="/api/v1/teams/{teamId}/widgets/data",
     *      tags={"Widgets"},
     *      summary="WidgetController@getWidgetData",
     *      description="Fetch lightweight data (id, name, etc.) for multiple teams across datasets, tools, collections, and DURS",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="team_ids",
     *          in="query",
     *          required=true,
     *          description="Comma-separated list of team IDs to filter data",
     *          example="1,2,3",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Aggregated data retrieved successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="datasets", type="array", @OA\Items(type="object")),
     *              @OA\Property(property="tools", type="array", @OA\Items(type="object")),
     *              @OA\Property(property="collections", type="array", @OA\Items(type="object")),
     *              @OA\Property(property="durs", type="array", @OA\Items(type="object"))
     *          )
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Invalid or missing teamIds parameter",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="team_ids parameter is required")
     *          )
     *      )
     * )
     */
    public function getWidgetData(Request $request, int $teamId): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $loggingContext = $this->getLoggingContext($request);
        $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        try {
            $teamIdsParam = $request->query('team_ids');
            if (!$teamIdsParam) {
                return response()->json([
                    'message' => 'team_ids parameter is required',
                ], Config::get('statuscodes.STATUS_BAD_REQUEST.code'));
            }


            $teamIds = is_array($teamIdsParam)
                ? $teamIdsParam
                : explode(',', $teamIdsParam);

            $teamIds = array_map('intval', $teamIds);

            $datasets = Dataset::whereIn('team_id', $teamIds)
                ->where('status', 'ACTIVE')
                ->get(['team_id', 'id'])
                ->map(fn ($dataset) => [
                    'id' => $dataset->id,
                    'title' => $dataset->getTitle(),
                    'team_id' => $dataset->team_id,
                    'team_name' => optional($dataset->team)->name,
                ]);

            $tools = Tool::whereIn('team_id', $teamIds)
                ->where('status', 'ACTIVE')
                ->get(['id', 'name', 'team_id'])
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'team_id' => $s->team_id,
                    'team_name' => optional($s->team)->name,
                ]);

            $collections = Collection::whereIn('team_id', $teamIds)
                ->where('status', 'ACTIVE')
                ->get(['id', 'name', 'team_id'])
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'team_id' => $c->team_id,
                    'team_name' => optional($c->team)->name,
                ]);

            $durs = Dur::whereIn('team_id', $teamIds)
                ->where('status', 'ACTIVE')
                ->get(['id', 'project_title', 'team_id'])
                ->map(fn ($du) => [
                    'id' => $du->id,
                    'name' => $du->project_title,
                    'team_id' => $du->team_id,
                    'team_name' => optional($du->team)->name,
                ]);



            return response()->json([ 'data' => [
                'datasets' => $datasets,
                'tools' => $tools,
                'collections' => $collections,
                'durs' => $durs,
            ]], Config::get('statuscodes.STATUS_OK.code'));
        } catch (\Exception $e) {
            Auditor::log([
                   'user_id' => (int)$jwtUser['id'],
                   'team_id' => $teamId,
                   'action_type' => 'EXCEPTION',
                   'action_name' => class_basename($this) . '@'.__FUNCTION__,
                   'description' => $e->getMessage(),
               ]);
            \Log::info($e->getMessage(), $loggingContext);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v1/teams/{teamId}/widgets/{id}/data",
     *      operationId="retrieve_widget_data",
     *      summary="Retrieve data related to a widget",
     *      description="Fetches datasets, data uses, scripts, and collections linked to a widget",
     *      tags={"Widgets"},
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="teamId",
     *          in="path",
     *          required=true,
     *          description="Team ID",
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="Widget ID",
     *          @OA\Schema(type="integer")
     *      ),
     *     @OA\Parameter(
     *         name="domain_origin",
     *         in="query",
     *         required=true,
     *         description="Optional domain URL to check against the widget's permitted_domains list",
     *         @OA\Schema(type="string", example="https://example.com")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden — domain not permitted for this widget",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="forbidden — domain not permitted for this widget")
     *         )
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="Widget data retrieved successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="datasets", type="array", @OA\Items(type="object")),
     *              @OA\Property(property="data_uses", type="array", @OA\Items(type="object")),
     *              @OA\Property(property="scripts", type="array", @OA\Items(type="object")),
     *              @OA\Property(property="collections", type="array", @OA\Items(type="object"))
     *          )
     *      ),
     *      @OA\Response(response=404, description="Widget not found")
     * )
     */
    public function retrieveData(Request $request, int $teamId, int $id)
    {
        try {
            $domainOrigin = $request->query('domain_origin');
            if (!$domainOrigin) {
                return response()->json(['message' => Config::get('statuscodes.STATUS_BAD_REQUEST.message')], Config::get('statuscodes.STATUS_BAD_REQUEST.code'));
            }
            $widget = Widget::where('id', $id)
                ->where('team_id', $teamId)
                ->first();

            if (!$widget) {
                return response()->json(['message' =>  Config::get('statuscodes.STATUS_NOT_FOUND.message')], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
            }

            if ($domainOrigin) {
                $permittedDomains = is_string($widget->permitted_domains)
                    ? array_map('trim', explode(',', $widget->permitted_domains))
                    : [];

                $normalizedOrigin = rtrim(preg_replace('#^https?://#', '', strtolower($domainOrigin)), '/');

                $allowed = collect($permittedDomains)
                    ->map(fn ($d) => rtrim(preg_replace('#^https?://#', '', strtolower($d)), '/'))
                    ->contains(fn ($d) => $normalizedOrigin === $d);

                if (!$allowed) {
                    return response()->json([
                        'message' => 'forbidden — domain not permitted for this widget',
                    ], Config::get('statuscodes.STATUS__FORBIDDEN.code'));
                }
            }


            $datasetIds     = is_string($widget->included_datasets) ? array_filter(explode(',', $widget->included_datasets)) : ($widget->included_datasets ?? []);
            $dataUseIds     = is_string($widget->included_data_uses) ? array_filter(explode(',', $widget->included_data_uses)) : ($widget->included_data_uses ?? []);
            $scriptIds      = is_string($widget->included_scripts) ? array_filter(explode(',', $widget->included_scripts)) : ($widget->included_scripts ?? []);
            $collectionIds  = is_string($widget->included_collections) ? array_filter(explode(',', $widget->included_collections)) : ($widget->included_collections ?? []);


            $datasetIds     = array_map('intval', $datasetIds);
            $dataUseIds     = array_map('intval', $dataUseIds);
            $scriptIds      = array_map('intval', $scriptIds);
            $collectionIds  = array_map('intval', $collectionIds);
            if (!empty($datasetIds)) {
                $ids = implode(',', $datasetIds);

                $datasets = DB::select("
                    WITH normalized AS (
                        SELECT
                            d.id,
                            d.team_id,
                            dv.id AS dataset_version_id,
                        CASE
                            WHEN JSON_VALID(dv.metadata)
                                AND JSON_TYPE(JSON_EXTRACT(dv.metadata, '$')) = 'STRING'
                            THEN JSON_UNQUOTE(JSON_EXTRACT(dv.metadata, '$'))
                            ELSE dv.metadata
                        END AS metadata
                        FROM datasets d
                        JOIN dataset_versions dv ON dv.dataset_id = d.id
                        WHERE dv.version = (
                            SELECT MAX(dv2.version)
                            FROM dataset_versions dv2
                            WHERE dv2.dataset_id = d.id
                        )
                        AND d.id IN ($ids)
                    )
                    SELECT
                        id,
                        team_id,
                        dataset_version_id,
                        JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.metadata.summary.title')) AS title,
                        JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.metadata.summary.shortTitle')) AS short_title,
                        JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.metadata.summary.description')) AS description,
                        JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.metadata.summary.keywords')) AS raw_keywords,
                        JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.metadata.summary.populationSize')) AS population_size,
                        JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.metadata.provenance.temporal.startDate')) AS start_date,
                        JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.metadata.provenance.temporal.endDate')) AS end_date,
                        JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.metadata.summary.publisher.name')) AS publisher
                    FROM normalized
                ");
            } else {
                $datasets = [];
            }

            if (!empty($dataUseIds)) {
                $dataUses = Dur::with('team:id,name')
                    ->whereIn('id', $dataUseIds)
                    ->get(['id', 'project_title', 'organisation_name', 'team_id'])
                    ->map(function ($du) {
                        $dataset = DB::table('dur as d')
                            ->join('dur_has_dataset_version as dhdv', 'dhdv.dur_id', '=', 'd.id')
                            ->join('dataset_versions as dv', 'dv.id', '=', 'dhdv.dataset_version_id')
                            ->join('datasets as ds', 'ds.id', '=', 'dv.dataset_id')
                            ->whereNull('d.deleted_at')
                            ->where('d.id', $du->id)
                            ->where('ds.status', 'ACTIVE')
                            ->select([
                                'ds.id as dataset_id',
                               DB::raw("
                                JSON_UNQUOTE(
                                    JSON_EXTRACT(
                                        CASE
                                            WHEN JSON_VALID(dv.metadata)
                                                AND JSON_TYPE(JSON_EXTRACT(dv.metadata, '$')) = 'STRING'
                                            THEN JSON_UNQUOTE(JSON_EXTRACT(dv.metadata, '$'))
                                            ELSE dv.metadata
                                        END,
                                        '$.metadata.summary.title'
                                    )
                                ) AS dataset_title
                            "),
                            DB::raw('(
                                    SELECT COUNT(DISTINCT ds2.id)
                                    FROM dur_has_dataset_version dhdv2
                                    JOIN dataset_versions dv2 ON dv2.id = dhdv2.dataset_version_id
                                    JOIN datasets ds2 ON ds2.id = dv2.dataset_id
                                    WHERE dhdv2.dur_id = d.id
                                ) as dataset_count'),
                            ])
                            ->limit(1)
                            ->first();

                        return [
                            'id' => $du->id,
                            'name' => $du->project_title,
                            'team_name' => $du->team?->name,
                            'team_id' => $du->team?->id,
                            'organisation_name' => $du->organisation_name,
                            'dataset' => $dataset,
                        ];
                    });
            } else {
                $dataUses = [];
            }

            if (!empty($scriptIds)) {
                $placeholders = implode(',', array_fill(0, count($scriptIds), '?'));
                $scripts = DB::select(
                    "SELECT id, name, description FROM tools WHERE id IN ($placeholders)",
                    $scriptIds
                );
            } else {
                $scripts = [];
            }

            if (!empty($collectionIds)) {
                $placeholders = implode(',', array_fill(0, count($collectionIds), '?'));
                $collections = DB::select(
                    "SELECT id, name, image_link FROM collections WHERE id IN ($placeholders)",
                    $collectionIds
                );
            } else {
                $collections = [];
            }


            return response()->json([
                'datasets' => $datasets,
                'data_uses' => $dataUses,
                'scripts' => $scripts,
                'collections' => $collections,
                'widget' => [
                    'widget_name' => $widget->widget_name,
                    'size_width'  => $widget->size_width,
                    'size_height'  => $widget->size_height,
                    'unit'  => $widget->unit,
                    'include_search_bar'  => $widget->include_search_bar,
                    'include_cohort_link'  => $widget->include_cohort_link,
                    'keep_proportions' => $widget->keep_proportions,
                ]
            ], Config::get('statuscodes.STATUS_OK.code'));

        } catch (Exception $e) {
            \Log::error('Error retrieving widget data', [
                'team_id' => $teamId,
                'widget_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => Config::get('statuscodes.STATUS_SERVER_ERROR.message')], Config::get('statuscodes.STATUS_SERVER_ERROR.code'));
        }
    }


    /**
     * @OA\Post(
     *      path="/api/v1/teams/{teamId}/widgets",
     *      operationId="create_widget",
     *      summary="Create a new widget",
     *      description="Creates a new widget for a given team",
     *      tags={"Widgets"},
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="teamId",
     *          in="path",
     *          description="Team ID the widget belongs to",
     *          required=true,
     *          example="5",
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     * @OA\JsonContent(
     *         required={"widget_name"},
     *         @OA\Property(property="widget_name", type="string", example="A really nice name"),
     *         @OA\Property(property="size_width", type="integer", example=400),
     *         @OA\Property(property="size_height", type="integer", example=300),
     *         @OA\Property(property="unit", type="string", enum={"px","%","rem"}, example="px"),
     *         @OA\Property(property="include_search_bar", type="boolean", example=true),
     *         @OA\Property(property="include_cohort_link", type="boolean", example=false),
     *         @OA\Property(property="keep_proportions", type="boolean", example=true),
     *
     *         @OA\Property(
     *             property="permitted_domains",
     *             type="array",
     *             @OA\Items(type="string", example="example.com"),
     *             example={"example.com", "example.org"}
     *         ),
     *
     *         @OA\Property(
     *             property="included_datasets",
     *             type="array",
     *             @OA\Items(type="integer", example=1),
     *             example={1,2,3}
     *         ),
     *
     *         @OA\Property(
     *             property="included_data_uses",
     *             type="array",
     *             @OA\Items(type="integer", example=10),
     *             example={10,11}
     *         ),
     *
     *         @OA\Property(
     *             property="included_scripts",
     *             type="array",
     *             @OA\Items(type="integer", example=5),
     *             example={5,6}
     *         ),
     *
     *         @OA\Property(
     *             property="included_collections",
     *             type="array",
     *             @OA\Items(type="integer", example=99),
     *             example={99,100}
     *         ),
     *         @OA\Property(
     *             property="data_custodian_entities_ids",
     *             type="array",
     *             @OA\Items(type="integer", example=99),
     *             example={99,100}
     *         ),
     *
     *     )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Widget created successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="success"),
     *              @OA\Property(property="data", type="object")
     *          )
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Validation failed",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="validation error")
     *          )
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Server error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error")
     *          )
     *      )
     * )
     */
    public function store(Request $request, int $teamId)
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $loggingContext = $this->getLoggingContext($request);
        $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        try {
            $validated = $request->validate([
                'widget_name'          => 'required|string|max:255',
                'size_width'           => 'nullable|integer',
                'size_height'          => 'nullable|integer',
                'unit'                 => 'nullable|string|in:px,%,rem',
                'include_search_bar'   => 'boolean',
                'include_cohort_link'  => 'boolean',
                'keep_proportions'     => 'boolean',
                'permitted_domains'    => 'nullable|array',
                'data_custodian_entities_ids' => 'nullable|array',
                'included_datasets'    => 'nullable|array',
                'included_data_uses'   => 'nullable|array',
                'included_scripts'     => 'nullable|array',
                'included_collections' => 'nullable|array',
            ]);

            $validated['team_id'] = $teamId;
            $arrayFields = [
                        'included_datasets',
                        'included_data_uses',
                        'included_scripts',
                        'included_collections',
                        'permitted_domains',
                        'data_custodian_entities_ids'
                    ];

            foreach ($arrayFields as $field) {
                if (isset($validated[$field]) && is_array($validated[$field])) {
                    $validated[$field] = implode(',', array_filter($validated[$field]));
                }
            }
            $widget = Widget::create($validated);

            return response()->json([
                 'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                 'data' => $widget->id,
             ], Config::get('statuscodes.STATUS_CREATED.code'));

        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            \Log::info($e->getMessage(), $loggingContext);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/v1/teams/{teamId}/widgets/{id}",
     *     tags={"Widgets"},
     *     summary="Update an existing widget",
     *     description="Updates an existing widget for a given team ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="teamId",
     *         in="path",
     *         required=true,
     *         description="Team ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Widget ID",
     *         @OA\Schema(type="integer", example=12)
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="widget_name", type="string", example="Updated Widget Name"),
     *             @OA\Property(property="size_width", type="integer", example=600),
     *             @OA\Property(property="size_height", type="integer", example=400),
     *             @OA\Property(property="unit", type="string", enum={"px","%","rem"}, example="px"),
     *             @OA\Property(property="include_search_bar", type="boolean", example=true),
     *             @OA\Property(property="include_cohort_link", type="boolean", example=false),
     *             @OA\Property(property="keep_proportions", type="boolean", example=true),
     *
     *             @OA\Property(
     *                 property="permitted_domains",
     *                 type="array",
     *                 @OA\Items(type="string", example="example.com"),
     *                 example={"example.com", "example.org"}
     *             ),
     *
     *             @OA\Property(
     *                 property="included_datasets",
     *                 type="array",
     *                 @OA\Items(type="integer", example=1),
     *                 example={1,2,3}
     *             ),
     *
     *             @OA\Property(
     *                 property="included_data_uses",
     *                 type="array",
     *                 @OA\Items(type="integer", example=10),
     *                 example={10,11}
     *             ),
     *
     *             @OA\Property(
     *                 property="data_custodian_entities_ids",
     *                 type="array",
     *                 @OA\Items(type="integer", example=10),
     *                 example={10,11}
     *             ),
     *
     *             @OA\Property(
     *                 property="included_scripts",
     *                 type="array",
     *                 @OA\Items(type="integer", example=5),
     *                 example={5,6}
     *             ),
     *
     *             @OA\Property(
     *                 property="included_collections",
     *                 type="array",
     *                 @OA\Items(type="integer", example=99),
     *                 example={99,100}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Widget successfully updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="success"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Widget not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function update(Request $request, int $teamId, int $id)
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $loggingContext = $this->getLoggingContext($request);
        $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        try {
            $widget = Widget::where('id', $id)
                ->where('team_id', $teamId)
                ->first();

            if (! $widget) {
                return response()->json(['message' => 'not found'], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
            }

            $validated = $request->validate([
            'widget_name'          => 'sometimes|string|max:255',
            'size_width'           => 'sometimes|integer',
            'size_height'          => 'sometimes|integer',
            'unit'                 => 'sometimes|string|in:px,%,rem',
            'include_search_bar'   => 'sometimes|boolean',
            'include_cohort_link'  => 'sometimes|boolean',
            'keep_proportions'     => 'sometimes|boolean',
            'permitted_domains'    => 'sometimes|array|nullable',
            'included_datasets'    => 'sometimes|array|nullable',
            'included_data_uses'   => 'sometimes|array|nullable',
            'included_scripts'     => 'sometimes|array|nullable',
            'included_collections' => 'sometimes|array|nullable',
            'data_custodian_entities_ids' => 'sometimes|array|nullable',

        ]);
            foreach (['permitted_domains', 'included_datasets', 'included_data_uses', 'included_scripts', 'included_collections', 'data_custodian_entities_ids'] as $field) {
                if (isset($validated[$field]) && is_array($validated[$field])) {
                    $validated[$field] = implode(',', $validated[$field]);
                }
            }

            $widget->update($validated);

            return response()->json([
                'message' => 'success',
                'data' => $widget
            ], Config::get('statuscodes.STATUS_OK.code'));

        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            \Log::info($e->getMessage(), $loggingContext);

            throw new Exception($e->getMessage());
        }
    }


    /**
     * @OA\Delete(
     *      path="/api/v1/teams/{teamId}/widgets/{id}",
     *      operationId="delete_widget",
     *      summary="Delete a widget",
     *      description="Soft delete a widget belonging to a specific team",
     *      tags={"Widgets"},
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="teamId",
     *         in="path",
     *         description="Team ID",
     *         required=true,
     *         example="5",
     *         @OA\Schema(type="integer"),
     *      ),
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Widget ID",
     *         required=true,
     *         example="1",
     *         @OA\Schema(type="integer"),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Widget not found",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="not found")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Widget deleted successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="success")
     *          )
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Server error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error")
     *          )
     *      )
     * )
     */
    public function destroy(Request $request, int $teamId, int $id)
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $loggingContext = $this->getLoggingContext($request);
        $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        try {
            $widget = Widget::where('id', $id)
                ->where('team_id', $teamId)
                ->first();

            if (! $widget) {
                return response()->json([
                    'message' => 'not found',
                ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
            }

            $widget->delete();

            return response()->json([
                'message' => 'success',
            ], Config::get('statuscodes.STATUS_OK.code'));

        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            \Log::info($e->getMessage(), $loggingContext);

            throw new Exception($e->getMessage());
        }

    }
}
