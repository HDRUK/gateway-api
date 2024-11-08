<?php

namespace App\Http\Controllers\Api\V1;

use Auditor;
use CloudLogger;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class TestController extends Controller
{
    public function __construct()
    {
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

    public function testPubSubService(Request $request): JsonResponse
    {
        Auditor::log([
            'user_id' => 1,
            'action_type' => 'CREATE',
            'action_name' => 'action service test',
            'description' => 'description test',
        ]);

        return response()->json(['status' => 'success']);
    }

    public function testGCPLogger(Request $request): JsonResponse
    {
        CloudLogger::write([
            'type' => 'send array',
            'user_id' => 1,
            'action_type' => 'CREATE',
            'action_name' => 'action service test',
            'description' => 'description test',
        ]);
        CloudLogger::write('send string');

        return response()->json(['status' => 'success']);
    }

}
