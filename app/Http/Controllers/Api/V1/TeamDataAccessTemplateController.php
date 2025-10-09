<?php

namespace App\Http\Controllers\Api\V1;

use Auditor;
use Config;
use Exception;
use App\Exceptions\UnauthorizedException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Http\Requests\DataAccessTemplate\GetTeamDataAccessTemplate;
use App\Http\Requests\DataAccessTemplate\DeleteDataAccessTemplateFile;
use App\Http\Traits\RequestTransformation;
use App\Models\DataAccessTemplate;
use App\Models\Upload;

class TeamDataAccessTemplateController extends Controller
{
    use RequestTransformation;

    /**
     * @OA\Get(
     *      path="/api/v1/teams/{teamId}/dar/templates",
     *      summary="List of dar templates belonging to a team",
     *      description="List of dar templates belonging to a team",
     *      tags={"TeamDataAccessTemplate"},
     *      summary="TeamDataAccessTemplate@index",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *        name="published",
     *        in="query",
     *        description="Template publication status to filter by (true, false)",
     *        example="true",
     *        @OA\Schema(
     *          type="string",
     *          description="Template publication status to filter by",
     *        ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer", example="123"),
     *                      @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                      @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                      @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                      @OA\Property(property="team_id", type="integer", example="1"),
     *                      @OA\Property(property="user_id", type="integer", example="1"),
     *                      @OA\Property(property="published", type="boolean", example="true"),
     *                      @OA\Property(property="locked", type="boolean", example="false"),
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function index(GetTeamDataAccessTemplate $request, int $teamId): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $filterPublished = isset($input['published']) ? $request->boolean('published') : null;
            $filterPublishedDefined = !is_null($filterPublished);

            $templates = DataAccessTemplate::where('team_id', $teamId)
            ->when($filterPublishedDefined, function ($query) use ($filterPublished) {
                return $query->where('published', $filterPublished);
            })
            ->with(['questions','files']);

            $counts = $templates->get()
                ->select('published')
                ->groupBy('published')
                ->map->count();

            $countsRenamed = collect([
                'active_count' => $counts[1] ?? 0,
                'non_active_count' => $counts[0] ?? 0,
            ]);

            $templates = $templates->paginate(
                Config::get('constants.per_page'),
                ['*'],
                'page'
            );

            $templates = $countsRenamed->merge($templates);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessTemplate get all by team',
            ]);

            return response()->json(
                $templates
            );
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
     *    path="/api/v1/teams/{teamId}/dar/templates/count/{field}",
     *    operationId="team_dar_template_count_unique_fields",
     *    tags={"TeamDataAccessTemplates"},
     *    summary="TeamDataAccessTemplateController@count",
     *    description="Get Counts for distinct entries of a field in the model",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="teamId",
     *       in="path",
     *       description="Team id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="Team id",
     *       ),
     *    ),
     *    @OA\Parameter(
     *       name="field",
     *       in="path",
     *       description="name of the field to perform a count on",
     *       required=true,
     *       example="published",
     *       @OA\Schema(
     *          type="string",
     *          description="published field",
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
    public function count(GetTeamDataAccessTemplate $request, int $teamId, string $field): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $counts = DataAccessTemplate::where('team_id', '=', $teamId)
                ->select($field)
                ->get()
                ->groupBy($field)
                ->map->count();

            if ($field === 'published') {
                $counts = collect([
                    'active_count' => $counts[1] ?? 0,
                    'non_active_count' => $counts[0] ?? 0,
                ]);
            }

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'TeamDataAccessTemplate count',
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
     * @OA\Delete(
     *      path="/api/v1/teams/{teamId}/dar/templates/{id}/files/{fileId}",
     *      summary="Delete a file associated with a DAR template",
     *      description="Delete a file associated with a DAR template",
     *      tags={"TeamDataAccessTemplate"},
     *      summary="TeamDataAccessTemplate@destroyFile",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DAR template id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DAR template id",
     *         ),
     *      ),
     *      @OA\Parameter(
     *         name="fileId",
     *         in="path",
     *         description="File id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="File id",
     *         ),
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="unauthorized")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="success")
     *          ),
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error")
     *          )
     *      )
     * )
     */
    public function destroyFile(DeleteDataAccessTemplateFile $request, int $teamId, int $id, int $fileId): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $template = DataAccessTemplate::where('id', $id)->first();
            if ($template->team_id != $teamId) {
                throw new UnauthorizedException(
                    "Team does not have permission to use this endpoint to delete this template file."
                );
            }

            $file = Upload::where('id', $fileId)->first();

            Storage::disk(env('SCANNING_FILESYSTEM_DISK', 'local_scan') . '_scanned')
                ->delete($file->file_location);

            $file->delete();

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessTemplate ' . $id . ' file ' . $fileId . ' deleted',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));

        } catch (UnauthorizedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));
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

}
