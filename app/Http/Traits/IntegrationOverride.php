<?php

namespace App\Http\Traits;

use App\Models\Application;

trait IntegrationOverride
{
    private function overrideTeamId(mixed &$teamId, array $input): void
    {
        if (isset($input['app-id'][0]) && isset($input['client-id'][0])) {
            $application = Application::where('app_id', $input['app-id'][0])
                ->where('client_id', $input['client-id'][0])->first();
            
            if ($application) {
                $teamId = $application->team_id;
            }
        }
    }

    private function overrideUserId(mixed &$userId, array $input): void
    {
        if (isset($input['app-id'][0]) && isset($input['client-id'][0])) {
            $application = Application::where('app_id', $input['app-id'][0])
                ->where('client_id', $input['client-id'][0])->first();
            
            if ($application) {
                $userId = $application->user_id;
            }
        }
    }

    private function injectApplicationDatasetDefaults(): array
    {
        if (isset($input['app-id'][0]) && isset($input['client-id'][0])) {
            $application = Application::where('app_id', $input['app-id'][0])
                ->where('client_id', $input['client-id'][0])->first();

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