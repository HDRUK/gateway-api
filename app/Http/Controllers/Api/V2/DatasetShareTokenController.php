<?php

namespace App\Http\Controllers\Api\V2;

use Config;
use Auditor;
use Exception;
use App\Models\Team;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\DatasetShareToken;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Traits\CheckAccess;
use App\Http\Traits\CollabMetadataSeed;
use App\Http\Controllers\Controller;
use App\Context\PartnerContext;
use Laravel\Pennant\Feature;

class DatasetShareTokenController extends Controller
{
    use CheckAccess;
    use CollabMetadataSeed;

    private const EXPIRY_WEEKS = 3;

    private const FEATURE_FLAG = 'CollaborativeMetadataOnboarding';

    public function __construct(
        private readonly PartnerContext $partnerContext,
    ) {
    }

    /**
     * List share tokens for a team.
     */
    public function index(Request $request, int $teamId): JsonResponse
    {
        if (!Feature::active(self::FEATURE_FLAG)) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        $input = $request->all();
        $this->checkAccess($input, $teamId, null, 'team', $request->header());

        $tokens = DatasetShareToken::where('team_id', $teamId)
            ->with('dataset')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'message' => Config::get('statuscodes.STATUS_OK.message'),
            'data' => $tokens,
        ], Config::get('statuscodes.STATUS_OK.code'));
    }

    /**
     * Create an empty draft dataset (create_origin=COLLAB) and a share token
     * pointing to it, for public collaborative onboarding.
     */
    public function store(Request $request, int $teamId): JsonResponse
    {
        if (!Feature::active(self::FEATURE_FLAG)) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        $input = $request->all();
        $this->checkAccess($input, $teamId, null, 'team', $request->header());
        $jwtUser = $request->jwtUser();

        try {
            $team = Team::where('id', $teamId)->first();

            $dataset = Dataset::create([
                'user_id' => $jwtUser['id'] ?? null,
                'team_id' => $teamId,
                'created' => now(),
                'updated' => now(),
                'submitted' => now(),
                'pid' => (string) \Illuminate\Support\Str::uuid(),
                'create_origin' => Dataset::ORIGIN_COLLAB,
                'status' => Dataset::STATUS_DRAFT,
                'is_cohort_discovery' => false,
                'partner_context' => $this->partnerContext->getPartner(),
            ]);

            $seed = $this->collabMetadataSeed($team, $dataset->id);
            $seed['summary']['title'] = 'Untitled draft';

            DatasetVersion::create([
                'dataset_id' => $dataset->id,
                'version' => 1,
                'title' => 'Untitled draft',
                'short_title' => null,
                'metadata' => [
                    'metadata' => $seed,
                    'original_metadata' => $seed,
                    'gwdmVersion' => Config::get('metadata.GWDM.version'),
                ],
                'gwdm_version' => Config::get('metadata.GWDM.version'),
            ]);

            $token = DatasetShareToken::create([
                'team_id' => $teamId,
                'dataset_id' => $dataset->id,
                'created_by' => $jwtUser['id'] ?? null,
                'token' => DatasetShareToken::generateToken(),
                'expires_at' => now()->addWeeks(self::EXPIRY_WEEKS),
            ]);

            Auditor::log([
                'user_id' => $jwtUser['id'] ?? null,
                'team_id' => $teamId,
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Dataset share token created for dataset ' . $dataset->id,
            ]);

            return response()->json([
                'message' => 'created',
                'data' => $token,
            ], 201);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Revoke a share token. The underlying draft dataset is left untouched.
     */
    public function destroy(Request $request, int $teamId, int $id): JsonResponse
    {
        if (!Feature::active(self::FEATURE_FLAG)) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        $input = $request->all();
        $this->checkAccess($input, $teamId, null, 'team', $request->header());

        $token = DatasetShareToken::where('id', $id)->where('team_id', $teamId)->first();
        if (!$token) {
            return response()->json([
                'message' => 'not found',
            ], 404);
        }

        $token->update(['revoked_at' => now()]);

        return response()->json([
            'message' => Config::get('statuscodes.STATUS_OK.message'),
        ], Config::get('statuscodes.STATUS_OK.code'));
    }
}
