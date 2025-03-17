<?php

namespace App\Http\Traits;

use App\Models\Application;
use Illuminate\Http\Request;
use App\Exceptions\UnauthorizedException;

trait IntegrationOverride
{
    private function overrideBothTeamAndUserId(mixed &$teamId, mixed &$userId, array $input): void
    {
        if (isset($input['x-application-id']) && isset($input['x-client-id'])) {
            $application = Application::where('app_id', $input['x-application-id'])
                ->where('client_id', $input['x-client-id'])->first();

            if ($application) {
                $teamId = $application->team_id;
                $userId = $application->user_id;
            }
        }
    }

    private function overrideTeamId(mixed &$teamId, array $input): void
    {
        if (isset($input['x-application-id']) && isset($input['x-client-id'])) {
            $application = Application::where('app_id', $input['x-application-id'])
                ->where('client_id', $input['x-client-id'])->first();

            if ($application) {
                $teamId = $application->team_id;
            }
        }
    }

    private function overrideUserId(mixed &$userId, array $input): void
    {
        if (isset($input['x-application-id']) && isset($input['x-client-id'])) {
            $application = Application::where('app_id', $input['x-application-id'])
                ->where('client_id', $input['x-client-id'])->first();

            if ($application) {
                $userId = $application->user_id;
            }
        }
    }

    private function injectApplicationDatasetDefaults(array $input): array
    {
        if (isset($input['x-application-id']) && isset($input['x-client-id'])) {
            $application = Application::where('app_id', $input['x-application-id'])
                ->where('client_id', $input['x-client-id'])->first();

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
    private function checkAppCanHandleDataset(int $datasetTeamId, Request $request): void
    {
        $teamId = null;
        $this->overrideTeamId($teamId, $request->headers->all());

        if ($datasetTeamId != $teamId) {
            throw new UnauthorizedException(
                'This Application is not allowed to interact with datasets from another team!'
            );
        }
    }
}
