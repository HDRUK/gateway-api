<?php

namespace App\Http\Controllers\Api\V2;

use Auditor;
use Config;
use Exception;
use App\Auth\TokenDenylist;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TokenRevocationController extends Controller
{
    /**
     * @OA\Post(
     *    path="/api/v2/admin/tokens/revoke_all",
     *    operationId="revoke_all_tokens_v2",
     *    tags={"Admin"},
     *    summary="TokenRevocationController@revokeAll",
     *    description="Revoke every Gateway JWT issued up to now, including the caller's own. All users must sign in again.",
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success"),
     *          @OA\Property(property="data", type="object",
     *             @OA\Property(property="revoked_before", type="string", example="2026-10-26T12:00:00.000000+00:00"),
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response=401,
     *       description="Unauthorized",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="unauthorized")
     *       )
     *    ),
     * )
     */
    public function revokeAll(Request $request, TokenDenylist $denylist): JsonResponse
    {
        $jwtUser = $request->jwtUser();

        try {
            $cutoff = CarbonImmutable::now();
            $denylist->revokeAllIssuedBefore($cutoff);

            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'All tokens issued before ' . $cutoff->toIso8601String() . ' revoked',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => [
                    'revoked_before' => $cutoff->format('Y-m-d\TH:i:s.uP'),
                ],
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }
}
