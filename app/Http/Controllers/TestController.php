<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestController extends Controller
{
    public function __construct() 
    {
        //
    }

    public function test()
    {
        return response()->json([
            'message' => 'Lorem ipsum dolor sit amet, consectetur adip'
        ]);
    }
}
