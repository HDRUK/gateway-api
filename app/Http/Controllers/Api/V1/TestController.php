<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Exceptions\NotFoundException;
use App\Http\Requests\TestValidationRequest;

class TestController extends Controller
{
    public function __construct() 
    {
        //
    }

    /**
     * 
     * Test with Jwt credentials
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function test(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Lorem ipsum dolor sit amet, consectetur adip',
            'request_method' => $request->method(),
            'request_body' => $request->all(),
        ]);
    }

    public function testValidation(TestValidationRequest $request): JsonResponse
    {
        return response()->json([
            'message' => 'Lorem ipsum dolor sit amet, consectetur adip',
        ]);
    }

    public function testException(): JsonResponse
    {
        throw new NotFoundException('Dataset not found');
        
        // return response()->json([
        //     'message' => 'Lorem ipsum dolor sit amet, consectetur adip',
        // ]);
    }
}
