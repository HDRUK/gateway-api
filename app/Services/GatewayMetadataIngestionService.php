<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Team;
use App\Models\Federation;
use App\Http\Traits\MetadataOnboard;
use Exception;
use Illuminate\Database\Eloquent\Collection;

class GatewayMetadataIngestionService
{
    use MetadataOnboard;

    private int $teamId = -1;
    private string $timezone = 'Europe/London';
    private int $federationId;

    public function setTeam(int $teamId): void
    {
        $this->teamId = $teamId;
    }

    public function getTeam(): int
    {
        return $this->teamId;
    }

    public function setFederation(int $federationId)
    {
        $this->federationId = $federationId;
        return $this;
    }

    public function storeMetadata($input): mixed
    {
        $metadataResult = $this->metadataOnboard(
            $input,
            Team::where('id', $this->teamId)->first()->toArray(),
            null,
            null,
            false
        );

        if ($metadataResult['translated']) {
            return true;
        }


        throw new Exception('metadata cannot be validated');
    }

    public function getActiveFederations(): Collection
    {
        return Federation::with('team')->where([
            'enabled' => 1,
            'tested' => 1,
            'run_time_hour' => Carbon::now($this->timezone)->hour,
            'run_time_minute' => Carbon::now($this->timezone)->minute,
            'is_running' => 0,
        ])->get();
    }

    public function getActiveFederationsById(): ?Federation
    {
        return Federation::with('team')->where([
            'enabled' => 1,
            'tested' => 1,
            'id' => $this->federationId,
            'is_running' => 0,
        ])->first();
    }
}
