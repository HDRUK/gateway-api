<?php

namespace App\Http\Controllers\Api\V1;

use Auditor;
use Illuminate\Http\Request;
use App\Services\PubSubService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\CloudLoggerService;

class TestController extends Controller
{
    protected $cloudLogger;

    public function __construct(CloudLoggerService $cloudLogger) 
    {
        $this->cloudLogger = $cloudLogger;
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
        $this->cloudLogger->write([
            'type' => 'send array',
            'user_id' => 1,
            'action_type' => 'CREATE',
            'action_name' => 'action service test',
            'description' => 'description test',
        ]);
        $this->cloudLogger->write('send string');

        return response()->json(['status' => 'success']);
    }
}
