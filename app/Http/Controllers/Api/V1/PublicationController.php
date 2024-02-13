<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Exception;

use App\Models\Publication;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

use App\Http\Requests\Publication\GetPublication;
use App\Http\Requests\Publication\EditPublication;
use App\Http\Requests\Publication\CreatePublication;
use App\Http\Requests\Publication\DeletePublication;
use App\Http\Requests\Publication\UpdatePublication;

use App\Http\Traits\RequestTransformation;

class PublicationController extends Controller
{
    use RequestTransformation;

    public function index(Request $request): JsonResponse
    {
        $publications = Publication::all()->paginate(Config::get('constants.per_page'), ['*'], 'page');
        return response()->json(
            $publications
        );
    }

    public function show(GetRequest $request, int $id): JsonResponse
    {
        $publication = Publication::findOrFail($id);
        if ($publication) {
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $publication,
            ], Config::get('statuscodes.STATUS_OK.code'));
        }

        return response()->json([
            'message' => Config::get('statuscodes.STATUS_NOT_FOUND.message')
        ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
    }

    public function store(CreatePublication $request): JsonResponse
    {
        try {
            $input = $request->all();
            $publication = Publication::create($input);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $publication->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function update(UpdatePublication $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $publication = Publication::where('id', $id)->update($input);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $publication,
            ]);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function edit(EditPublication $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $publication = Publication::where('id', $id)->update($input);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $publication,
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function destroy(DeletePublication $request, int $id): JsonResponse
    {
        try {
            $publication = Publication::findOrFail($id);
            if ($publication) {
                $publication->delete();

                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                ], Config::get('statuscodes.STATUS_OK.code'));
            }

            throw new NotFoundException();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
