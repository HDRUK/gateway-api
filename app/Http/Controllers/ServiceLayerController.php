<?php

namespace App\Http\Controllers;

use Auditor;
use Config;
use Exception;
use App\Models\Federation;
use App\Models\Dataset;

use App\Http\Controllers\Api\V1\DatasetController;

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

    public function getDatasetFromPid(Request $request, string $pid)
    {
        $dataset = Dataset::with(['versions' => fn($version) => $version->withTrashed()->latest()->first()])
            ->where('pid', "=", $pid)
            ->first();

        $response = [
            "pid" => $dataset->pid,
            "version" => $dataset->versions[0]->version,
            "metadata" => $dataset->versions[0]->metadata['metadata'],
        ];

        return response()->json($response);
    }

    public function getDatasets(Request $request)
    {
        $datasets = Dataset::with(['versions' => fn($version) => $version->withTrashed()->latest()->first()])
                    ->when($request->has('team_id'), 
                            function ($query) use ($request) {
                                return $query->where("team_id","=",$request->query('team_id'));
                            })
                    ->when($request->has('create_origin'), 
                            function ($query) use ($request) {
                                return $query->where("create_origin","=",$request->query('create_origin'));
                            })
                    ->get();

        $response = array();
        foreach ($datasets as $dataset){
            if(count($dataset->versions)==0){
                continue;
            }
            $response[$dataset->pid] = [
                "version" => $dataset->versions[0]->version,
                "metadata" => $dataset->versions[0]->metadata['metadata'],
            ];
        }

        return response()->json($response);
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
