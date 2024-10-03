<?php

namespace App\Http\Controllers\Api\V1;

use Auditor;
use Config;
use Exception;

use App\Http\Controllers\Controller;
use App\Jobs\ScanFileUpload;
use App\Models\Upload;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    /**
     * @OA\Post(
     *      path="/api/v1/files",
     *      summary="Upload a file to the gateway-api",
     *      description="Upload a file to the gateway-api via scanning sub-service",
     *      tags={"Upload"},
     *      summary="Upload@upload",
     *      @OA\Parameter(
     *          name="entity_flag",
     *          in="query",
     *          description="Flag to indicate the purpose of the file upload e.g. dur-from-upload",
     *          example="dur-from-upload",
     *          @OA\Schema(
     *              type="string",
     *              description="Flag to indicate the purpose of the file upload (dur-from-upload, dataset-from-upload, structural-upload, team-image, collection-image)",
     *          ),
     *      ),
     *      @OA\Parameter(
     *          name="team_id",
     *          in="query",
     *          description="Id of team associated with the file upload",
     *          example="10",
     *          @OA\Schema(
     *              type="integer",
     *              description="Id of team associated with the file upload if applicable",
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Upload complete",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="int")
     *          )
     *      )
     * )
     */
    public function upload(Request $request): JsonResponse
    {
        try {
            $input = $request->all();
            $file  = $request->file('file');
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
            $fileSystem = env('SCANNING_FILESYSTEM_DISK', 'local_scan');
            $entityFlag = $request->query('entity_flag', 'none');
            $teamId = $request->query('team_id', null);
            $inputSchema = $request->query('input_schema', null);
            $inputVersion = $request->query('input_version', null);
            $outputSchema = $request->query('output_schema', null);
            $outputVersion = $request->query('output_version', null);
            $elasticIndexing = $request->boolean('elastic_indexing', true);
            $datasetId = $request->query('dataset_id', null);
            $collectionId = $request->query('collection_id', null);

            // store unscanned
            $storedFilename = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs(
                '',
                $storedFilename,
                $fileSystem . '.unscanned'
            );

            // if there is an error, storeAs returns false and does not actually throw...
            if ($filePath === false) {
                throw new Exception($file->getError());
            }

            // write to uploads
            $upload = Upload::create([
                'filename' => $file->getClientOriginalName(),
                'file_location' => $filePath,
                'user_id' => (int)$jwtUser['id'],
                'status' => 'PENDING'
            ]);

            if ($collectionId === 'undefined') {
                $collectionId = $upload->id;
            }
            // spawn scan job
            ScanFileUpload::dispatch(
                (int)$upload->id,
                $fileSystem,
                $entityFlag,
                (int)$jwtUser['id'],
                (int)$teamId,
                $inputSchema,
                $inputVersion,
                $outputSchema,
                $outputVersion,
                $elasticIndexing,
                $datasetId,
                (int)$collectionId,
            );

            // audit log
            Auditor::log([
                'action_type' => 'POST',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'File upload',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => Upload::where('id', $upload->id)->first()
            ]);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'POST',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v1/files/{id}",
     *      summary="Get the scanning status of an upload",
     *      description="Get the scanning status of an upload",
     *      tags={"Upload"},
     *      summary="Upload@show",
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="upload id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="upload id",
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="filename", type="string"),
     *                      @OA\Property(property="file_location", type="string"),
     *                      @OA\Property(property="user_id", type="string"),
     *                      @OA\Property(property="status", type="string"),
     *                      @OA\Property(property="error", type="string")
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $upload = Upload::findOrFail($id);

            if ($upload['structural_metadata']) {
                $upload['structural_metadata'] = json_decode($upload['structural_metadata']);
            }

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Show uploaded file',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $upload
            ]);
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
     *      path="/api/v1/files/processed/{id}",
     *      summary="Get the content of a processed file",
     *      description="Get the content of a processed file",
     *      tags={"Upload"},
     *      summary="Upload@content",
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="upload id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="upload id",
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="filename", type="string"),
     *                      @OA\Property(property="content", type="string")
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function content(Request $request, int $id): JsonResponse
    {
        try {
            $upload = Upload::findOrFail($id);
            if ($upload->status === 'PENDING') {
                Auditor::log([
                    'action_type' => 'GET',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'Get uploaded file content failed due to pending malware scan',
                ]);
                return response()->json([
                    'message' => 'File scan is pending'
                ]);
            } elseif ($upload->status === 'FAILED') {
                Auditor::log([
                    'action_type' => 'GET',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'Get uploaded file content failed due to failed malware scan',
                ]);
                return response()->json([
                    'message' => 'File failed scan, content cannot be retrieved'
                ]);
            } else {
                $contents = Storage::disk(env('SCANNING_FILESYSTEM_DISK', 'local_scan') . '.scanned')
                    ->get($upload->file_location);

                Auditor::log([
                    'action_type' => 'GET',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'Get uploaded file content: ' . $upload->file_location,
                ]);
                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                    'data' => [
                        'filename' => $upload->filename,
                        'content' => $contents
                    ]
                ]);
            }
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }
}
