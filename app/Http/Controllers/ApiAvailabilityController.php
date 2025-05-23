<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class ApiAvailabilityController extends Controller
{
    /**
     * @OA\Get(
     *      path="/status",
     *      summary="Returns the API availability status",
     *      description="Used to ping the API. To test for availability",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *      )
     *  )
     *
     * @return mixed
     */
    public function index(): mixed
    {
        return response()->json([
            'status' => 'OK',
        ], 200);
    }
}
