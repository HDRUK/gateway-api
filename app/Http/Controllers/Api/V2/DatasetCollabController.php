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
use App\Services\Gwdm\SourceSchema;
use App\Exceptions\UnprocessableException;
use App\Services\OnboardingForm\JsonSchema;
use App\Services\OnboardingForm\OnboardingFormService;
use App\Services\OnboardingForm\OnboardingSchemaCatalogueService;
use MetadataManagementController as MMC;
use Laravel\Pennant\Feature;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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

    /** Collab mode is built on the schema-driven onboarding form only. */
    private const SCHEMA_FORM_FLAG = 'SchemaOnboardingForm';

    public function __construct(
        private readonly OnboardingFormService $onboardingFormService,
        private readonly OnboardingSchemaCatalogueService $schemaCatalogue,
    ) {
    }

    /**
     * Folds the feature flags into the same null-return path as an invalid
     * token, so a disabled feature and an unknown/expired/revoked token are
     * indistinguishable to an anonymous caller - this is a public,
     * unauthenticated endpoint, so there's no reason to leak which one it
     * is.
     */
    private function resolveToken(string $token): ?DatasetShareToken
    {
        if (!Feature::active(self::FEATURE_FLAG) || !Feature::active(self::SCHEMA_FORM_FLAG)) {
            return null;
        }

        $shareToken = DatasetShareToken::where('token', $token)->first();
        if (!$shareToken || !$shareToken->isValid()) {
            return null;
        }

        // Tokens are only revoked on activation, so a link must also die with
        // its draft: once the team archives or deletes it (soft-deleted rows
        // aren't loaded), the collaborator can no longer read or write it.
        if (!$shareToken->dataset || $shareToken->dataset->status !== Dataset::STATUS_DRAFT) {
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

        $dataset = $shareToken->dataset;
        $team = Team::where('id', $dataset->team_id)->first();

        // The same hydrated form the authenticated form_hydration endpoint
        // returns; model/version come from the form's schema picker.
        try {
            $form = $this->onboardingFormService->hydrate(
                $team,
                $dataset,
                $request->query('model'),
                $request->query('version'),
            );
        } catch (UnprocessableException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

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
                // Shown read-only as the data custodian, which the save forces
                // to this team. Team names are already public on the Gateway.
                'team_name' => $team->name,
                'metadata' => $dataset->lastMetadata(),
                'schema' => [
                    'model' => Config::get('metadata.GWDM.name'),
                    'version' => Config::get('metadata.GWDM.version'),
                ],
                'form' => $form,
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

        // Validated here rather than in a FormRequest so that, like every other
        // failure on this public endpoint, a disabled feature or dead token
        // still reads as a 404 before anything about the body is revealed.
        $validator = Validator::make($request->all(), [
            'metadata' => ['required', 'array'],
            'metadata.metadata' => ['required', 'array'],
            'metadata.schemaModel' => ['nullable', 'string', 'required_with:metadata.schemaVersion'],
            'metadata.schemaVersion' => ['nullable', 'string', 'required_with:metadata.schemaModel'],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'INVALID_ARGUMENT',
                'message' => 'Invalid argument(s)',
                'errors' => $validator->errors(),
            ], 400);
        }

        $input = $request->all();

        try {
            $dataset = $shareToken->dataset;
            $team = Team::where('id', $dataset->team_id)->first();

            // Fields normally set server-side by MetadataOnboard::metadataOnboard()
            // during a standard authenticated create (version/issued/modified/
            // revisions, and the data custodian) - the collab flow bypasses that
            // entirely, and the custodian picker is disabled for anonymous
            // collaborators, so these must be forced here rather than trusted
            // from the submission. `issued` is preserved across saves; everything
            // else is refreshed on every save.
            //
            // The form's schema picker means the submission may be in any
            // offered schema, so the declared one must be offered, and each
            // field is only forced where that schema actually has it. An
            // undeclared submission keeps the legacy behaviour (force all).
            $inputSchema = $input['metadata']['schemaModel'] ?? null;
            $inputVersion = $input['metadata']['schemaVersion'] ?? null;

            $schema = null;
            if ($inputSchema !== null) {
                if (!$this->schemaCatalogue->offers($inputSchema, $inputVersion)) {
                    return response()->json([
                        'message' => 'Schema ' . $inputSchema . ' ' . $inputVersion . ' is not offered for onboarding',
                    ], 422);
                }
                $schema = $this->onboardingFormService->schemaFor($inputSchema, $inputVersion);
            }

            $existingIssued = $dataset->lastMetadata()['original_metadata']['issued'] ?? null;
            $seed = $this->collabMetadataSeed($team, $dataset->id, $existingIssued);

            $forced = [
                'version' => $seed['version'],
                'issued' => $seed['issued'],
                'modified' => $seed['modified'],
                'revisions' => $seed['revisions'],
                'summary.dataCustodian' => $seed['summary']['dataCustodian'],
            ];
            foreach ($forced as $path => $value) {
                if ($schema === null || JsonSchema::hasPath($schema, $path)) {
                    data_set($input['metadata']['metadata'], $path, $value);
                }
            }

            $payload = $this->extractMetadata($input['metadata']);
            $payload['extra'] = [
                'id' => $dataset->id,
                'pid' => $dataset->pid,
                'datasetType' => 'Health and disease',
                'publisherId' => $team['pid'],
                'publisherName' => $team['name'],
            ];

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

            // The raw Traser response isn't returned to anonymous callers.
            if (!$traserResponse['wasTranslated']) {
                return response()->json([
                    'message' => 'metadata is in an unknown format and cannot be processed.',
                ], 400);
            }

            // Re-checked under a lock: the team may have activated or archived
            // the draft while this request was translating it.
            $written = DB::transaction(function () use ($dataset, $traserResponse, $submittedMetadata, $inputSchema, $inputVersion, $shareToken) {
                $status = Dataset::whereKey($dataset->id)->lockForUpdate()->value('status');
                if ($status !== Dataset::STATUS_DRAFT) {
                    return false;
                }

                $this->updateMetadataVersion(
                    $dataset,
                    $traserResponse['metadata'],
                    $submittedMetadata,
                    SourceSchema::declared($inputSchema, $inputVersion),
                );
                $shareToken->update(['last_submitted_at' => now()]);

                return true;
            });

            if (!$written) {
                return response()->json([
                    'message' => 'invalid or expired token',
                ], 404);
            }

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
