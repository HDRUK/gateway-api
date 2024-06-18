<?php

namespace App\Http\Controllers;

use Auditor;
use Config;
use Exception;
use App\Models\Federation;
use App\Models\Dataset;

use App\Http\Controllers\Api\V1\DatasetController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http; 



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
        //$datasets = Dataset::with(['versions' => fn($version) => $version->withTrashed()->latest()->first()])
        $datasets = Dataset::with('versions')
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

            $metadataVersions = [];
            foreach($dataset->versions as $version){
                $gwdmVersion = $version->metadata['gwdmVersion'];
                if(version_compare($gwdmVersion,"1.0",">")){
                    $metadata = $version->metadata['metadata'];
                    $metadataVersions[] = $metadata['required']['version'];
                }
            }

            $response[$dataset->pid] = [
                "versions" => $metadataVersions,
            ];

            if ($request->has('onlyVersions')) {
                if($request->boolean('onlyVersions')){
                    continue;
                }
            }
            $response[$dataset->pid]["metadata"] = $dataset->versions[0]->metadata['metadata'];

        }

        return response()->json($response);
    }

    public function audit(Request $request)
    {
        $input = $request->all();

        try {
            $retVal = Auditor::log($input);

            if ($retVal) {
                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                ]);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function quba(Request $request){
        return $this->forwardRequest($request, 
            env("QUBA_SERVICE"), 
            "api/services/quba/"
        );
    }

     public function daras(Request $request){
        return $this->forwardRequest($request, 
           env("DARAS_SERVICE"), 
           "api/services/daras/"
        );
    }

    private function forwardRequest(Request $request, string $baseUrl, string $apiPath) {
        // Extract the request path
        $path = $request->path();

        // Build the full URL by appending the request path to the base URL
        $subPath = substr($path, strpos($path,$apiPath) + strlen($apiPath));
        $url = $baseUrl . "/" . $subPath;

        // Forward the request to the external API service
        $response = Http::send($request->method(), $url, [
            'query' => $request->query(),
            'body' => $request->getContent(),
        ]);

        $statusCode = $response->status();

        $responseData = $response->json();

        return response()->json($responseData, $statusCode);
    }


}
