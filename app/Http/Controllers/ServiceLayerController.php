<?php

namespace App\Http\Controllers;

use Auditor;
use Config;
use Exception;
use App\Models\Federation;

use Illuminate\Http\Request;


class ServiceLayerController extends Controller
{
    public function getActiveFederationApplications(Request $request)
    {
        $federations = Federation::with('team')
            ->where('enabled', 1)
            ->where('tested', 1)
            ->get();
        return response()->json($federations);
    }

    public function setFederationInvalidRunState(Request $request, int $id)
    {
        try {
            $federation = Federation::where('id', $id)->first();
            $federation->enabled = 0;
            $federation->tested = 0;
            if ($federation->save()) {
                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                ]);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function audit(Request $request)
    {
        $input = $request->all();

        try {
            $retVal = Auditor::log(
                $input['user_id'],
                $input['team_id'],
                $input['action_type'],
                $input['action_service'],
                $input['description']
            );

            if ($retVal) {
                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                ]);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
