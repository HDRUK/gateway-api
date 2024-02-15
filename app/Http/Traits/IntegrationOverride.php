<?php

namespace App\Http\Traits;

use App\Models\Application;

trait IntegrationOverride
{
    private function overrideTeamId(mixed &$teamId, array $input): void
    {
        if (isset($input['X-Application-ID']) && isset($input['X-Client-ID'])) {
            $application = Application::where('app_id', $input['X-Application-ID'])
                ->where('client_id', $input['X-Client-ID'])->first();
            
            if ($application) {
                $teamId = $application->team_id;
            }
        }
    }

    private function overrideUserId(mixed &$userId, array $input): void
    {
        if (isset($input['X-Application-ID']) && isset($input['X-Client-ID'])) {
            $application = Application::where('app_id', $input['X-Application-ID'])
                ->where('client_id', $input['X-Client-ID'])->first();
            
            if ($application) {
                $userId = $application->user_id;
            }
        }
    }

    private function injectApplicationDatasetDefaults(array $input): array
    {
        if (isset($input['X-Application-ID']) && isset($input['X-Client-ID'])) {
            $application = Application::where('app_id', $input['X-Application-ID'])
                ->where('client_id', $input['X-Client-ID'])->first();

            if ($application) {
                return [
                    'user_id' => $application->user_id,
                    'team_id' => $application->team_id,
                    'create_origin' => 'API',
                    'status' => 'ACTIVE',
                ];
            }
        }

        return [];
    }
}