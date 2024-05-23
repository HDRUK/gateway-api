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
        $fileSystem = env('FILESYSTEM_DISK', 'local_scan');

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
            return response()->json([
                'message' => 'File scan is pending'
            ]);
        } else if ($upload->status === 'FAILED') {
            return response()->json([
                'message' => 'File failed scan, content cannot be retrieved'
            ]);
        } else {
            $contents = Storage::disk(env('FILESYSTEM_DISK', 'local_scan') . '.scanned')
                ->get($upload->file_location);
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
