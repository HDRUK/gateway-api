<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dur\GetDur;
use App\Models\Dur;

class DurController extends Controller
{
    /**
     * @OA\Get(
     *    path="/api/v1/dur",
     *    operationId="fetch_all_dur",
     *    tags={"Data Use Registers"},
     *    summary="DurController@index",
     *    description="Returns a list of data use registers",
     *    @OA\Response(
     *       response=200,
     *       description="Success",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string"),
     *          @OA\Property(property="data", type="array",
     *             @OA\Items(
     *                @OA\Property(property="id", type="integer", example="123"),
     *                @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                @OA\Property(property="non_gateway_datasets", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="non_gateway_applicants", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="funders_and_sponsors", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="other_approval_committees", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="gateway_outputs_tools", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="gateway_outputs_papers", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="non_gateway_outputs", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="project_title", type="string", example="Birth order and cord blood DNA methylation"),
     *                @OA\Property(property="project_id_text", type="string", example="B3649"),
     *                @OA\Property(property="organisation_name", type="string", example="LA-SER Europe Ltd"),
     *                @OA\Property(property="organisation_sector", type="string", example="Independent Sector Organisation"),
     *                @OA\Property(property="lay_summary", type="string", example="Non laudantium consequatur nulla minima. ..."),
     *                @OA\Property(property="technical_summary", type="string", example="Sint odit veritatis nam excepturi natus. ..."),
     *                @OA\Property(property="latest_approval_date", type="datetime", example="2023-04-03 12:00:00"),
     *                @OA\Property(property="manual_upload", type="boolean", example="0"),
     *                @OA\Property(property="rejection_reason", type="string", example=""),
     *                @OA\Property(property="sublicence_arrangements", type="string", example=""),
     *                @OA\Property(property="public_benefit_statement", type="string", example="Officiis provident sint iure. ..."),
     *                @OA\Property(property="data_sensitivity_level", type="string", example="Anonymous"),
     *                @OA\Property(property="project_start_date", type="datetime", example="2023-04-03 12:00:00"),
     *                @OA\Property(property="project_end_date", type="datetime", example="2023-04-03 12:00:00"),
     *                @OA\Property(property="access_date", type="datetime", example="2023-04-03 12:00:00"),
     *                @OA\Property(property="accredited_researcher_status", type="string", example="No"),
     *                @OA\Property(property="confidential_description", type="string", example=""),
     *                @OA\Property(property="dataset_linkage_description", type="string", example=""),
     *                @OA\Property(property="duty_of_confidentiality", type="string", example="Statutory exemption to flow confidential data without consent"),
     *                @OA\Property(property="legal_basis_for_data_article6", type="string", example="Ad labore atque asperiores eum quia. ..."),
     *                @OA\Property(property="legal_basis_for_data_article9", type="string", example="Quisquam illum ut porro quia. ..."),
     *                @OA\Property(property="national_data_optout", type="string", example="Not applicable"),
     *                @OA\Property(property="organisation_id", type="string", example="grid.10025.36"),
     *                @OA\Property(property="privacy_enhancements", type="string", example="Voluptatem veritatis dolorem amet culpa qui qui. ..."),
     *                @OA\Property(property="request_category_type", type="string", example="Health Services & Delivery"),
     *                @OA\Property(property="request_frequency", type="string", example="Public Health Research"),
     *                @OA\Property(property="access_type", type="string", example="Efficacy & Mechanism Evaluation"),
     *                @OA\Property(property="mongo_object_dar_id", type="string", example="MOBJIDDAR-2387"),
     *                @OA\Property(property="technicalSummary", type="string", example="Similique officia dolor nam. ..."),
     *                @OA\Property(property="enabled", type="boolean", example="1"),
     *                @OA\Property(property="last_activity", type="datetime", example="2023-04-03 12:00:00"),
     *                @OA\Property(property="counter", type="integer", example="34319"),
     *                @OA\Property(property="mongo_object_id", type="string", example="5f32a7d53b1d85c427e97c01"),
     *                @OA\Property(property="mongo_id", type="string", example="38873389090594430"),
     *                @OA\Property(property="datasets", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="keywords", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="users", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="user", type="array", example="{}", @OA\Items()),
     *                @OA\Property(property="team", type="array", example="{}", @OA\Items()),
     *             ),
     *          ),
     *          @OA\Property(property="first_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/collections?page=1"),
     *          @OA\Property(property="from", type="integer", example="1"),
     *          @OA\Property(property="last_page", type="integer", example="1"),
     *          @OA\Property(property="last_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/collections?page=1"),
     *          @OA\Property(property="links", type="array", example="[]", @OA\Items(type="array", @OA\Items())),
     *          @OA\Property(property="next_page_url", type="string", example="null"),
     *          @OA\Property(property="path", type="string", example="http:\/\/localhost:8000\/api\/v1\/collections"),
     *          @OA\Property(property="per_page", type="integer", example="25"),
     *          @OA\Property(property="prev_page_url", type="string", example="null"),
     *          @OA\Property(property="to", type="integer", example="3"),
     *          @OA\Property(property="total", type="integer", example="3"),
     *       )
     *    )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = request('perPage', Config::get('constants.per_page'));
        $dur = Dur::where('enabled', 1)
            ->with([
                'datasets', 
                'keywords',
                'users' => function ($query) {
                    $query->distinct('id');
                },
                'user',
                'team',
                ])->paginate($perPage);
        return response()->json(
            $dur
        );
    }

    /**
     * @OA\Get(
     *    path="/api/v1/dur/{id}",
     *    operationId="fetch_data_use_register",
     *    tags={"Data Use Registers"},
     *    summary="DurController@show",
     *    description="Get data use register by id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="data use register id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="data use register id",
     *       ),
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success"),
     *             @OA\Property(property="data", type="array",
     *                @OA\Items(type="object",
     *                @OA\Property(property="id", type="integer", example="123"),
     *                @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                @OA\Property(property="non_gateway_datasets", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="non_gateway_applicants", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="funders_and_sponsors", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="other_approval_committees", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="gateway_outputs_tools", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="gateway_outputs_papers", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="non_gateway_outputs", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="project_title", type="string", example="Birth order and cord blood DNA methylation"),
     *                @OA\Property(property="project_id_text", type="string", example="B3649"),
     *                @OA\Property(property="organisation_name", type="string", example="LA-SER Europe Ltd"),
     *                @OA\Property(property="organisation_sector", type="string", example="Independent Sector Organisation"),
     *                @OA\Property(property="lay_summary", type="string", example="Non laudantium consequatur nulla minima. ..."),
     *                @OA\Property(property="technical_summary", type="string", example="Sint odit veritatis nam excepturi natus. ..."),
     *                @OA\Property(property="latest_approval_date", type="datetime", example="2023-04-03 12:00:00"),
     *                @OA\Property(property="manual_upload", type="boolean", example="0"),
     *                @OA\Property(property="rejection_reason", type="string", example=""),
     *                @OA\Property(property="sublicence_arrangements", type="string", example=""),
     *                @OA\Property(property="public_benefit_statement", type="string", example="Officiis provident sint iure. ..."),
     *                @OA\Property(property="data_sensitivity_level", type="string", example="Anonymous"),
     *                @OA\Property(property="project_start_date", type="datetime", example="2023-04-03 12:00:00"),
     *                @OA\Property(property="project_end_date", type="datetime", example="2023-04-03 12:00:00"),
     *                @OA\Property(property="access_date", type="datetime", example="2023-04-03 12:00:00"),
     *                @OA\Property(property="accredited_researcher_status", type="string", example="No"),
     *                @OA\Property(property="confidential_description", type="string", example=""),
     *                @OA\Property(property="dataset_linkage_description", type="string", example=""),
     *                @OA\Property(property="duty_of_confidentiality", type="string", example="Statutory exemption to flow confidential data without consent"),
     *                @OA\Property(property="legal_basis_for_data_article6", type="string", example="Ad labore atque asperiores eum quia. ..."),
     *                @OA\Property(property="legal_basis_for_data_article9", type="string", example="Quisquam illum ut porro quia. ..."),
     *                @OA\Property(property="national_data_optout", type="string", example="Not applicable"),
     *                @OA\Property(property="organisation_id", type="string", example="grid.10025.36"),
     *                @OA\Property(property="privacy_enhancements", type="string", example="Voluptatem veritatis dolorem amet culpa qui qui. ..."),
     *                @OA\Property(property="request_category_type", type="string", example="Health Services & Delivery"),
     *                @OA\Property(property="request_frequency", type="string", example="Public Health Research"),
     *                @OA\Property(property="access_type", type="string", example="Efficacy & Mechanism Evaluation"),
     *                @OA\Property(property="mongo_object_dar_id", type="string", example="MOBJIDDAR-2387"),
     *                @OA\Property(property="technicalSummary", type="string", example="Similique officia dolor nam. ..."),
     *                @OA\Property(property="enabled", type="boolean", example="1"),
     *                @OA\Property(property="last_activity", type="datetime", example="2023-04-03 12:00:00"),
     *                @OA\Property(property="counter", type="integer", example="34319"),
     *                @OA\Property(property="mongo_object_id", type="string", example="5f32a7d53b1d85c427e97c01"),
     *                @OA\Property(property="mongo_id", type="string", example="38873389090594430"),
     *                @OA\Property(property="datasets", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="keywords", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="users", type="array", example="[]", @OA\Items()),
     *                @OA\Property(property="user", type="array", example="{}", @OA\Items()),
     *                @OA\Property(property="team", type="array", example="{}", @OA\Items()),
     *                ),
     *             ),
     *          ),
     *       ),
     *    ),
     * )
     */
    public function show(GetDur$request, int $id): JsonResponse
    {
        try {
            $dur = Dur::where(['id' => $id])
                ->with([
                    'datasets',
                    'keywords',
                    'users' => function ($query) {
                        $query->distinct('id');
                    },
                    'user',
                    'team',
                ])->get();

            return response()->json([
                'message' => 'success',
                'data' => $dur,
            ], 200);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
