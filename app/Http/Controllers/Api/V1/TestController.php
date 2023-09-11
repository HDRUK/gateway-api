<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

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

    public function testCheckAccess(Request $request)
    {
        return response()->json([
            'message' => 'TestController.checkRoles',
            'request_method' => $request->method(),
            'request_body' => $request->all(),
        ]);
    }
}
