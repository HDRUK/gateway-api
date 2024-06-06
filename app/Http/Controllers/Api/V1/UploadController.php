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
use Illuminate\Support\Facades\Http;
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
        $input = $request->all();
        $file  = $request->file('file');
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $fileSystem = env('SCANNING_FILESYSTEM_DISK', 'local_scan');

        // store unscanned
        $storedFilename = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs(
            '', $storedFilename, $fileSystem . '.unscanned'
        );

        // write to uploads
        $upload = Upload::create([
            'filename' => $file->getClientOriginalName(),
            'file_location' => $filePath,
            'user_id' => $jwtUser['id'],
            'status' => 'PENDING'
        ]);

        // spawn scan job
        ScanFileUpload::dispatch((int) $upload->id, $fileSystem);

        // audit log
        Auditor::log([
            'action_type' => 'POST',
            'action_service' => class_basename($this) . '@'.__FUNCTION__,
            'description' => "File upload",
        ]);

        return response()->json([
            'message' => Config::get('statuscodes.STATUS_OK.message'),
            'data' => Upload::where('id', $upload->id)->first()
        ]);
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
        $upload = Upload::findOrFail($id);

        Auditor::log([
            'action_type' => 'GET',
            'action_service' => class_basename($this) . '@'.__FUNCTION__,
            'description' => "Show uploaded file",
        ]);

        return response()->json([
            'message' => Config::get('statuscodes.STATUS_OK.message'),
            'data' => $upload
        ]);
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
        $upload = Upload::findOrFail($id);
        if ($upload->status === 'PENDING') {
            Auditor::log([
                'action_type' => 'GET',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Get uploaded file content failed due to pending malware scan",
            ]);
            return response()->json([
                'message' => 'File scan is pending'
            ]);
        } else if ($upload->status === 'FAILED') {
            Auditor::log([
                'action_type' => 'GET',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Get uploaded file content failed due to failed malware scan",
            ]);
            return response()->json([
                'message' => 'File failed scan, content cannot be retrieved'
            ]);
        } else {
            $contents = Storage::disk(env('SCANNING_FILESYSTEM_DISK', 'local_scan') . '.scanned')
                ->get($upload->file_location);
                
            Auditor::log([
                'action_type' => 'GET',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Get uploaded file content",
            ]);
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => [
                    'filename' => $upload->filename,
                    'content' => $contents
                ]
            ]);
        }
    }
}
