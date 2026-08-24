<?php

namespace App\Http\Controllers\Api\V2;

use Config;
use Auditor;
use Exception;
use App\Models\Team;
use App\Models\Dataset;
use App\Models\DatasetShareToken;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Traits\MetadataVersioning;
use App\Http\Traits\MetadataExtraction;
use App\Http\Traits\CollabMetadataSeed;
use App\Http\Controllers\Controller;
use MetadataManagementController as MMC;
use Laravel\Pennant\Feature;

/**
 * Public, unauthenticated endpoints for collaborative dataset onboarding.
 * Access is gated entirely by possession of a valid, unexpired, unrevoked
 * share token — see DatasetShareTokenController for token issuance.
 */
class DatasetCollabController extends Controller
{
    use MetadataVersioning;
    use MetadataExtraction;
    use CollabMetadataSeed;

    private const FEATURE_FLAG = 'CollaborativeMetadataOnboarding';

    /**
     * Folds the feature flag into the same null-return path as an invalid
     * token, so a disabled feature and an unknown/expired/revoked token are
     * indistinguishable to an anonymous caller - this is a public,
     * unauthenticated endpoint, so there's no reason to leak which one it
     * is.
     */
    private function resolveToken(string $token): ?DatasetShareToken
    {
        if (!Feature::active(self::FEATURE_FLAG)) {
            return null;
        }

        $shareToken = DatasetShareToken::where('token', $token)->first();
        if (!$shareToken || !$shareToken->isValid()) {
            return null;
        }

        return $shareToken;
    }

    public function show(Request $request, string $token): JsonResponse
    {
        $shareToken = $this->resolveToken($token);
        if (!$shareToken) {
            return response()->json([
                'message' => 'invalid or expired token',
            ], 404);
        }

        $dataset = Dataset::where('id', $shareToken->dataset_id)->first();
        $team = Team::where('id', $dataset->team_id)->first();

        return response()->json([
            'message' => Config::get('statuscodes.STATUS_OK.message'),
            'data' => [
                'dataset_id' => $dataset->id,
                'team_id' => $dataset->team_id,
                // Anonymous collaborators can't call the authenticated
                // GET /teams/{id} endpoint, but the web app's onboarding
                // form needs a fallback email for its hidden required
                // "contact point" fields - surface just this one field here
                // rather than exposing the full team record publicly.
                'team_contact_point' => $team->contact_point,
                'metadata' => $dataset->lastMetadata(),
                'schema' => [
                    'model' => Config::get('metadata.GWDM.name'),
                    'version' => Config::get('metadata.GWDM.version'),
                ],
                'expires_at' => $shareToken->expires_at,
                'ready_for_review_at' => $shareToken->ready_for_review_at,
            ],
        ], Config::get('statuscodes.STATUS_OK.code'));
    }

    /**
     * Lets the collaborator flag the draft as ready for the team admin to
     * review, without touching Dataset::status (still DRAFT, still only
     * promotable to ACTIVE by the admin via the normal authenticated flow).
     * Idempotent and reversible - `ready: false` un-marks it, e.g. if the
     * collaborator wants to keep editing after flagging it ready.
     */
    public function markReadyForReview(Request $request, string $token): JsonResponse
    {
        $shareToken = $this->resolveToken($token);
        if (!$shareToken) {
            return response()->json([
                'message' => 'invalid or expired token',
            ], 404);
        }

        $ready = $request->boolean('ready', true);
        $shareToken->update(['ready_for_review_at' => $ready ? now() : null]);

        return response()->json([
            'message' => Config::get('statuscodes.STATUS_OK.message'),
            'data' => [
                'ready_for_review_at' => $shareToken->ready_for_review_at,
            ],
        ], Config::get('statuscodes.STATUS_OK.code'));
    }

    public function update(Request $request, string $token): JsonResponse
    {
        $shareToken = $this->resolveToken($token);
        if (!$shareToken) {
            return response()->json([
                'message' => 'invalid or expired token',
            ], 404);
        }

        $input = $request->all();

        try {
            $dataset = Dataset::where('id', $shareToken->dataset_id)->first();
            $team = Team::where('id', $dataset->team_id)->first();

            // Fields normally set server-side by MetadataOnboard::metadataOnboard()
            // during a standard authenticated create (version/issued/modified/
            // revisions, and the data custodian) - the collab flow bypasses that
            // entirely, and the custodian picker is disabled for anonymous
            // collaborators, so these must be forced here rather than trusted
            // from the submission. `issued` is preserved across saves; everything
            // else is refreshed on every save.
            $existingIssued = $dataset->lastMetadata()['original_metadata']['issued'] ?? null;
            $seed = $this->collabMetadataSeed($team, $dataset->id, $existingIssued);

            $input['metadata']['metadata']['version'] = $seed['version'];
            $input['metadata']['metadata']['issued'] = $seed['issued'];
            $input['metadata']['metadata']['modified'] = $seed['modified'];
            $input['metadata']['metadata']['revisions'] = $seed['revisions'];
            $input['metadata']['metadata']['summary']['dataCustodian'] = $seed['summary']['dataCustodian'];

            $payload = $this->extractMetadata($input['metadata']);
            $payload['extra'] = [
                'id' => $dataset->id,
                'pid' => $dataset->pid,
                'datasetType' => 'Health and disease',
                'publisherId' => $team['pid'],
                'publisherName' => $team['name'],
            ];

            $inputSchema = $input['metadata']['schemaModel'] ?? null;
            $inputVersion = $input['metadata']['schemaVersion'] ?? null;
            $submittedMetadata = $input['metadata']['metadata'];

            // Collaborative submissions are always treated as drafts: validation
            // stays disabled and the dataset can never be promoted to ACTIVE from
            // this endpoint - only the team admin can do that via the normal
            // authenticated update path once they've reviewed the submission.
            $traserResponse = MMC::translateDataModelType(
                json_encode($payload),
                Config::get('metadata.GWDM.name'),
                Config::get('metadata.GWDM.version'),
                $inputSchema,
                $inputVersion,
                false,
                false
            );

            if (!$traserResponse['wasTranslated']) {
                return response()->json([
                    'message' => 'metadata is in an unknown format and cannot be processed.',
                    'details' => $traserResponse,
                ], 400);
            }

            $this->updateMetadataVersion(
                $dataset,
                $traserResponse['metadata'],
                $submittedMetadata,
            );

            $shareToken->update(['last_submitted_at' => now()]);

            Auditor::log([
                'team_id' => $dataset->team_id,
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Collaborative draft submission for dataset ' . $dataset->id,
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            throw new Exception($e->getMessage());
        }
    }
}
