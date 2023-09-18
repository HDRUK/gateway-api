<?php

namespace App\Http\Controllers;

use App\Models\Federation;

use Illuminate\Http\Request;

class ServiceLayerController extends Controller
{
    public function getActiveFederationApplications(Request $request)
    {
        $federations = Federation::with('team')->where('enabled', true)->get();
        return response()->json($federations);
    }
}
