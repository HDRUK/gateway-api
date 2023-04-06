<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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
     * @return mixed
     */
    public function test(Request $request): mixed
    {
        return response()->json([
            'message' => 'Lorem ipsum dolor sit amet, consectetur adip',
            'request_method' => $request->method(),
            'request_body' => $request->all(),
        ]);
    }
}
