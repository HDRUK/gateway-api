<?php

namespace App\Http\Controllers\Api\V1;

use Carbon\Carbon;

use App\Models\Publisher;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\Response;


class PublisherController extends Controller
{
    /**
     * @OA\Get(
     *      path="api/v1/publishers",
     *      summary="List of publishers",
     *      description="Returns a list of publishers enabled on the system",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer", example="123"),
     *                      @OA\Property(property="created_at", type="datetime", example="2023-04-11 12:00:00"),
     *                      @OA\Property(property="updated_at", type="datetime", example="2023-04-11 12:00:00"),
     *                      @OA\Property(property="enabled", type="boolean", example="1"),
     *                      @OA\Property(property="name", type="string", example="someName"),
     *                      @OA\Property(property="allows_messaging", type="boolean", example="1"),
     *                      @OA\Property(property="workflow_enabled", type="boolean", example="1"),
     *                      @OA\Property(property="access_requests_management", type="boolean", example="1"),
     *                      @OA\Property(property="uses_5_safes", type="boolean", example="1"),
     *                      @OA\Property(property="member_of", type="string", example="someOrg"),
     *                      @OA\Property(property="contact_point", type="string", example="someone@mail.com"),
     *                      @OA\Property(property="application_form_updated_by", type="integer", example="555"),
     *                      @OA\Property(property="application_form_updated_on", type="datetime", example="2023-04-11"), 
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function index(Request $request)
    {
        $publishers = Publisher::where('enabled', 1)->get();
        return response()->json([
            'data' => $publishers
        ]);
    }

    /**
     * @OA\Get(
     *      path="api/v1/publishers/{id}",
     *      summary="Return a single publisher",
     *      description="Return a single publisher",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-11 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-11 12:00:00"),
     *                  @OA\Property(property="enabled", type="boolean", example="1"),
     *                  @OA\Property(property="name", type="string", example="someName"),
     *                  @OA\Property(property="allows_messaging", type="boolean", example="1"),
     *                  @OA\Property(property="workflow_enabled", type="boolean", example="1"),
     *                  @OA\Property(property="access_requests_management", type="boolean", example="1"),
     *                  @OA\Property(property="uses_5_safes", type="boolean", example="1"),
     *                  @OA\Property(property="member_of", type="string", example="someOrg"),
     *                  @OA\Property(property="contact_point", type="string", example="someone@mail.com"),
     *                  @OA\Property(property="application_form_updated_by", type="integer", example="555"),
     *                  @OA\Property(property="application_form_updated_on", type="datetime", example="2023-04-11"),
     *              )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not found response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="not found"),
     *          )
     *      )
     * )
     */
    public function show(Request $request, int $id)
    {
        $publisher = Publisher::findOrFail($id);
        if ($publisher) {
            return response()->json([
                'data' => $publisher,
            ], 200);
        }

        return response()->json([
            'message' => 'not found'
        ], 404);
    }

    /**
     * @OA\Post(
     *      path="api/v1/publishers",
     *      summary="Create a new publisher",
     *      description="Creates a new publisher",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Publisher details",
     *          @OA\JsonContent(
     *              required={
     *                  "name", 
     *                  "enabled",
     *                  "allows_messaging",
     *                  "workflow_enabled",
     *                  "access_requests_management",
     *                  "uses_5_safes",
     *                  "member_of",
     *                  "contact_point",
     *                  "application_form_updated_by",
     *                  "application_form_updated_on",
     *              },
     *              @OA\Property(property="name", type="string", example="someName"),
     *              @OA\Property(property="allows_messaging", type="boolean", example="1"),
     *              @OA\Property(property="workflow_enabled", type="boolean", example="1"),
     *              @OA\Property(property="access_requests_management", type="boolean", example="1"),
     *              @OA\Property(property="uses_5_safes", type="boolean", example="1"),
     *              @OA\Property(property="member_of", type="string", example="someOrg"),
     *              @OA\Property(property="contact_point", type="string", example="someone@mail.com"),
     *              @OA\Property(property="application_form_updated_by", type="integer", example="555"),
     *              @OA\Property(property="application_form_updated_on", type="datetime", example="2023-04-11"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="success"),
     *             @OA\Property(property="data", type="integer", example="100")
     *          ),
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error")
     *          )
     *      )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'enabled' => 'required',
            'allows_messaging' => 'required',
            'workflow_enabled' => 'required',
            'access_requests_management' => 'required',
            'uses_5_safes' => 'required',
            'member_of' => 'required',
            'contact_point' => 'required',
            'application_form_updated_by' => 'required',
            'application_form_updated_on' => 'required',
        ]);

        $publisher = Publisher::create($request->post());
        if ($publisher) {
            return response()->json([
                'message' => 'success',
                'data' => $publisher->id,
            ], 200);
        }
        return response()->json([
            'message' => 'error',
        ], 500);
    }

    /**
     * @OA\Patch(
     *      path="api/v1/publishers/{id}",
     *      summary="Update a publisher",
     *      description="Update a publisher",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Publisher definition",
     *          @OA\JsonContent(
     *              required={
     *                  "name", 
     *                  "enabled",
     *                  "allows_messaging",
     *                  "workflow_enabled",
     *                  "access_requests_management",
     *                  "uses_5_safes",
     *                  "member_of",
     *                  "contact_point",
     *                  "application_form_updated_by",
     *                  "application_form_updated_on",
     *              },
     *              @OA\Property(property="name", type="string", example="someName"),
     *              @OA\Property(property="allows_messaging", type="boolean", example="1"),
     *              @OA\Property(property="workflow_enabled", type="boolean", example="1"),
     *              @OA\Property(property="access_requests_management", type="boolean", example="1"),
     *              @OA\Property(property="uses_5_safes", type="boolean", example="1"),
     *              @OA\Property(property="member_of", type="string", example="someOrg"),
     *              @OA\Property(property="contact_point", type="string", example="someone@mail.com"),
     *              @OA\Property(property="application_form_updated_by", type="integer", example="555"),
     *              @OA\Property(property="application_form_updated_on", type="datetime", example="2023-04-11"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not found response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="not found")
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="success"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-11 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-11 12:00:00"),
     *                  @OA\Property(property="name", type="string", example="someName"),
     *                  @OA\Property(property="allows_messaging", type="boolean", example="1"),
     *                  @OA\Property(property="workflow_enabled", type="boolean", example="1"),
     *                  @OA\Property(property="access_requests_management", type="boolean", example="1"),
     *                  @OA\Property(property="uses_5_safes", type="boolean", example="1"),
     *                  @OA\Property(property="member_of", type="string", example="someOrg"),
     *                  @OA\Property(property="contact_point", type="string", example="someone@mail.com"),
     *                  @OA\Property(property="application_form_updated_by", type="integer", example="555"),
     *                  @OA\Property(property="application_form_updated_on", type="datetime", example="2023-04-11"),
     *              )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error")
     *          )
     *      )
     * )
     */
    public function update(Request $request, int $publisher)
    {
        $request->validate([
            'name' => 'required',
            'enabled' => 'required',
            'allows_messaging' => 'required',
            'workflow_enabled' => 'required',
            'access_requests_management' => 'required',
            'uses_5_safes' => 'required',
            'member_of' => 'required',
            'contact_point' => 'required',
            'application_form_updated_by' => 'required',
            'application_form_updated_on' => 'required',
        ]);

        $publisher = Publisher::findOrFail($publisher);
        $body = $request->post();
        $publisher->name = $body['name'];
        $publisher->enabled = $body['enabled'];
        $publisher->allows_messaging = $body['allows_messaging'];
        $publisher->workflow_enabled = $body['workflow_enabled'];
        $publisher->access_requests_management = $body['access_requests_management'];
        $publisher->uses_5_safes = $body['uses_5_safes'];
        $publisher->member_of = $body['member_of'];
        $publisher->contact_point = $body['contact_point'];
        $publisher->application_form_updated_by = $body['application_form_updated_by'];
        $publisher->application_form_updated_on = $body['application_form_updated_on'];

        if ($publisher->save()) {
            return response()->json([
                'message' => 'success',
                'data' => $publisher,
            ], 200);
        } else {
            return response()->json([
                'message' => 'error',
            ], 500);
        }

        return response()->json([
            'message' => 'not found',
        ], 404);
    }
    
    /**
     * @OA\Delete(
     *      path="api/v1/publishers/{id}",
     *      summary="Delete a publisher",
     *      description="Delete a publisher",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=404,
     *          description="Not found response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="not found")
     *           ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="success")
     *          ),
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error")
     *          )
     *      )
     * )
     */
    public function destroy(Request $request, int $publisher)
    {
        $publisher = Publisher::findOrFail($publisher);
        if ($publisher) {
            $publisher->deleted_at = Carbon::now();
            $publisher->enabled = false;
            if ($publisher->save()) {
                return response()->json([
                    'message' => 'success',
                ], 200);
            }

            return response()->json([
                'message' => 'error',
            ], 500);
        }

        return response()->json([
            'message' => 'not found',
        ], 404);
    }
}
