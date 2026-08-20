<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class IntegrationDatasetController extends Controller
{
    private const GONE_MESSAGE = 'This endpoint has been retired. Please use the current Dataset API instead.';

    public function index(Request $request): JsonResponse
{
        return $this->gone();
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return $this->gone();
    }

    public function store(Request $request): JsonResponse
    {
        return $this->gone();
    }

    public function update(Request $request, int $id): JsonResponse
    {
        return $this->gone();
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        return $this->gone();
    }

    public function datasetTest(Request $request): JsonResponse
    {
        return $this->gone();
    }

    private function gone(): JsonResponse
    {
        return response()->json([
            'message' => self::GONE_MESSAGE,
        ], 410);
    }
}
