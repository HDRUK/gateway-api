<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\NotFoundException;
use App\Http\Controllers\Controller;
use App\Models\User;
use Auditor;
use Exception;
use Illuminate\Http\Request;
use Laravel\Pennant\Feature;

class FeatureController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/v1/features",
     *   operationId="FeatureIndex",
     *   tags={"Feature"},
     *   summary="List feature flags and their resolved values (global scope)",
     *   description="Returns a key/value map of feature names to their resolved values for the global (null) scope. Requires PENNANT_STORE=database.",
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *
     *     @OA\JsonContent(
     *       type="object",
     *
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         description="Map of feature name to resolved value (global scope)",
     *         additionalProperties=@OA\Schema(
     *           oneOf={
     *
     *             @OA\Schema(type="boolean"),
     *             @OA\Schema(type="string"),
     *             @OA\Schema(type="integer"),
     *             @OA\Schema(type="number"),
     *             @OA\Schema(type="array"),
     *             @OA\Schema(type="object")
     *           }
     *         ),
     *         example={
     *           "RQuest"=true,
     *           "Widgets"=false,
     *         }
     *       )
     *     )
     *   )
     * )
     */
    public function index(Request $request)
    {
        $this->requirePennantDatabaseStore();

        $names = $this->featureNames();

        // global configuration (null scope)
        $values = Feature::for(null)->values($names);

        return response()->json([
            'data' => $values,
        ], 200);
    }

    /**
     * @OA\Get(
     *   path="/api/v1/users/{userId}/features",
     *   operationId="FeatureIndexForUser",
     *   tags={"Feature"},
     *   summary="List feature flags and their resolved values for a user",
     *   description="Returns a key/value map of feature names to their resolved values for the given user scope. Requires PENNANT_STORE=database.",
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Parameter(
     *     name="userId",
     *     in="path",
     *     required=true,
     *     description="User ID",
     *
     *     @OA\Schema(type="integer", example=123)
     *   ),
     *
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *
     *     @OA\JsonContent(
     *       type="object",
     *
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         description="Map of feature name to resolved value for the user scope",
     *         additionalProperties=@OA\Schema(
     *           oneOf={
     *
     *             @OA\Schema(type="boolean"),
     *             @OA\Schema(type="string"),
     *             @OA\Schema(type="integer"),
     *             @OA\Schema(type="number"),
     *             @OA\Schema(type="array"),
     *             @OA\Schema(type="object")
     *           }
     *         ),
     *         example={
     *           "Widgets"=true,
     *           "RQuest"=true
     *         }
     *       )
     *     )
     *   )
     * )
     */
    public function indexForUser(Request $request, int $userId)
    {
        try {
            $user = User::find($userId);
            if (! $user) {
                return response()->json([
                    'message' => 'Cannot find this user',
                    'data' => [],
                ], 404);
            }

            $names = $this->featureNames();
            $userValues = Feature::for($user)->values($names);
            $values = $this->effectiveValuesForUser($user);

            return response()->json([
                'data' => $values,
                'userValues' => $userValues,
            ], 200);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *   path="/api/v1/features/me",
     *   operationId="FeatureIndexForMe",
     *   tags={"Feature"},
     *   summary="List feature flags and their resolved values for the current jwt user",
     *   description="Returns a key/value map of feature names to their resolved values for the given user scope. Requires PENNANT_STORE=database.",
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *
     *     @OA\JsonContent(
     *       type="object",
     *
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         description="Map of feature name to resolved value for the user scope",
     *         additionalProperties=@OA\Schema(
     *           oneOf={
     *
     *             @OA\Schema(type="boolean"),
     *             @OA\Schema(type="string"),
     *             @OA\Schema(type="integer"),
     *             @OA\Schema(type="number"),
     *             @OA\Schema(type="array"),
     *             @OA\Schema(type="object")
     *           }
     *         ),
     *         example={
     *           "Widgets"=true,
     *           "RQuest"=true
     *         }
     *       )
     *     )
     *   )
     * )
     */
    public function indexForMe(Request $request)
    {
        try {
            $user = $this->jwtUser($request);

            if (! $user) {
                return response()->json([
                    'message' => 'Cannot find this user',
                    'data' => [],
                ], 404);
            }

            return response()->json([
                'data' => $this->effectiveValuesForUser($user),
            ], 200);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *   path="/api/v1/features/{name}",
     *   operationId="FeatureToggleByName",
     *   tags={"Feature"},
     *   summary="Toggle a feature flag globally by name",
     *   description="Toggles the global (null-scope) value of a stored feature flag by name and returns the global active state after toggling. Requires PENNANT_STORE=database.",
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Parameter(
     *     name="name",
     *     in="path",
     *     required=true,
     *     description="Feature flag name",
     *
     *     @OA\Schema(type="string", example="new-checkout")
     *   ),
     *
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *
     *     @OA\JsonContent(
     *       type="object",
     *
     *       @OA\Property(
     *         property="data",
     *         type="boolean",
     *         description="Global (null-scope) active state after toggling",
     *         example=true
     *       )
     *     )
     *   )
     * )
     */
    public function toggleByName(Request $request, string $name)
    {
        try {
            $this->requirePennantDatabaseStore();

            $exists = \DB::table('features')->where('name', $name)->exists();
            if (! $exists) {
                return response()->json([
                    'message' => 'Cannot find feature='.$name,
                    'data' => [],
                ], 404);
            }

            $global = Feature::for(null);
            $global->active($name) ? Feature::deactivateForEveryone($name) : Feature::activateForEveryone($name);

            Feature::flushCache();

            return response()->json([
                'data' => $global->active($name),
            ], 200);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *   path="/api/v1/users/{userId}/features/{name}",
     *   operationId="FeatureToggleByNameForUser",
     *   tags={"Feature"},
     *   summary="Toggle a feature flag for a specific user",
     *   description="Toggles the value of a stored feature flag for the given user scope and returns the user's active state after toggling. Requires PENNANT_STORE=database. Returns 501 if PENNANT_STORE is not database.",
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Parameter(
     *     name="userId",
     *     in="path",
     *     required=true,
     *     description="User ID",
     *
     *     @OA\Schema(type="integer", example=123)
     *   ),
     *
     *   @OA\Parameter(
     *     name="name",
     *     in="path",
     *     required=true,
     *     description="Feature flag name",
     *
     *     @OA\Schema(type="string", example="new-checkout")
     *   ),
     *
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *
     *     @OA\JsonContent(
     *       type="object",
     *
     *       @OA\Property(
     *         property="data",
     *         type="boolean",
     *         description="User-scoped active state after toggling",
     *         example=true
     *       )
     *     )
     *   )
     * )
     */
    public function toggleByNameForUser(Request $request, int $userId, string $name)
    {
        try {
            $driver = $this->pennantDriver();
            if ($driver !== 'database') {
                return response()->json([
                    'message' => 'This endpoint requires PENNANT_STORE=database to toggle stored feature flags.',
                ], 501);
            }

            $user = User::find($userId);
            if (! $user) {
                throw new NotFoundException;
            }

            $exists = \DB::table('features')->where('name', $name)->exists();
            if (! $exists) {
                throw new NotFoundException;
            }

            $scoped = Feature::for($user);

            $scoped->active($name) ? $scoped->deactivate($name) : $scoped->activate($name);

            Feature::flushCache();

            return response()->json([
                'data' => $scoped->active($name),
            ], 200);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *   path="/api/v1/users/{userId}/features/{name}",
     *   operationId="FeatureDeleteByNameForUser",
     *   tags={"Feature"},
     *   summary="Delete (forget) a user-scoped feature override",
     *   description="Removes the stored override for the given feature name in the given user scope (falls back to global/default evaluation). Requires PENNANT_STORE=database.",
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Parameter(
     *     name="userId",
     *     in="path",
     *     required=true,
     *     description="User ID",
     *
     *     @OA\Schema(type="integer", example=123)
     *   ),
     *
     *   @OA\Parameter(
     *     name="name",
     *     in="path",
     *     required=true,
     *     description="Feature flag name",
     *
     *     @OA\Schema(type="string", example="new-checkout")
     *   ),
     *
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *
     *     @OA\JsonContent(
     *       type="object",
     *
     *       @OA\Property(
     *         property="data",
     *         type="boolean",
     *         description="User-scoped active state after deletion (may now reflect global/default evaluation)",
     *         example=false
     *       )
     *     )
     *   )
     * )
     */
    public function deleteByNameForUser(Request $request, int $userId, string $name)
    {
        try {
            $this->requirePennantDatabaseStore();

            $user = User::find($userId);
            if (! $user) {
                throw new NotFoundException;
            }

            $exists = \DB::table('features')->where('name', $name)->exists();
            if (! $exists) {
                throw new NotFoundException;
            }

            $scoped = Feature::for($user);
            $scoped->forget($name);
            Feature::flushCache();

            return response()->json([
                'data' => $scoped->active($name),
            ], 200);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    // Hidden from swagger docs intentionally (no @OA annotation)
    public function flushAllFeatures(Request $request)
    {
        Feature::flushCache();

        return response()->json([
            'message' => 'Feature cache flushed successfully.',
        ], 200);
    }

    // Hidden from swagger docs intentionally (no @OA annotation)
    public function flushAllUserFeatures(Request $request)
    {
        try {
            $this->requirePennantDatabaseStore();

            \DB::table('features')
                ->whereNotNull('scope')
                ->delete();

            Feature::flushCache();

            return response()->json([
                'message' => 'All user-scoped feature overrides flushed successfully (now falling back to global).',
            ], 200);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    // Hidden from swagger docs intentionally (no @OA annotation)
    public function flushUserFeatures(Request $request, int $userId)
    {
        try {
            $this->requirePennantDatabaseStore();

            $user = User::find($userId);
            if (! $user) {
                throw new NotFoundException;
            }

            $names = $this->featureNames();

            $scoped = Feature::for($user);

            foreach ($names as $name) {
                $scoped->forget($name);
            }

            Feature::flushCache();

            return response()->json([
                'message' => 'User-scoped feature overrides flushed successfully',
                'data' => [
                    'userId' => $userId,
                    'features_processed' => count($names),
                ],
            ], 200);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    private function pennantDriver(): string
    {
        $storeName = config('pennant.default', 'array');

        return config("pennant.stores.$storeName.driver", 'array');
    }

    private function requirePennantDatabaseStore(): void
    {
        if ($this->pennantDriver() !== 'database') {
            throw new Exception(
                'This endpoint requires PENNANT_STORE=database because it lists/toggles features from the database store.'
            );
        }
    }

    private function featureNames(): array
    {
        return \DB::table('features')
            ->distinct()
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    private function jwtUser(Request $request): ?User
    {
        $input = $request->all();
        $jwtUser = $input['jwt_user'] ?? null;
        $id = is_array($jwtUser) ? ($jwtUser['id'] ?? null) : null;

        return $id ? User::find($id) : null;
    }

    private function userOverrideValues(User $user, array $names): array
    {
        return \DB::table('features')
            ->where('scope', get_class($user).'|'.$user->getKey())
            ->whereIn('name', $names)
            ->pluck('value', 'name')
            ->map(function ($value) {
                $decoded = json_decode($value, true);

                return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
            })
            ->all();
    }

    private function effectiveValuesForUser(User $user): array
    {
        $this->requirePennantDatabaseStore();

        $names = $this->featureNames();

        $globalValues = Feature::for(null)->values($names);

        // Feature::for($user) scope defaults to false if the feature doesnt exist
        // - i cant work out how to get it default for the Feature::for(null) scope
        //   if the value doesnt exist (which could be true), rather than default to false

        // I've done this manually myself instead via:
        // - i dont love it, but it does work
        $userOverrides = $this->userOverrideValues($user, $names);

        return array_replace($globalValues, $userOverrides);
    }
}
