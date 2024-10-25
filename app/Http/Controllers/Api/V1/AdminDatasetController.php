<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;

use App\Models\Dataset;

class AdminDatasetController extends Controller
{
    public function triggerTermExtraction(Request $request): JsonResponse
    {
        $input = $request->all();

        $datsets = Dataset::select('id')->pluck('id');

        return response()->json(['datasets' => $datasets]);
    }
}
