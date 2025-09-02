<?php

namespace App\Services;

use Carbon\Carbon;

use App\Models\Team;
use App\Models\Federation;
use App\Http\Traits\MetadataOnboard;

use Illuminate\Database\Eloquent\Collection;

class GatewayMetadataIngestionService
{
    use MetadataOnboard;

    private int $teamId = -1;
    private string $timezone = 'Europe/London';

    public function setTeam(int $teamId): void
    {
        $this->teamId = $teamId;
    }

    public function getTeam(): int
    {
        return $this->teamId;
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

        return [
            'message' => 'metadata cannot be validated',
            'details' => $metadataResult['response'],
        ];
    }

    public function getActiveFederations(): Collection
    {
        return Federation::with('team')->where([
            'enabled' => 1,
            'tested' => 1,
            'run_time_hour' => Carbon::now($this->timezone)->hour,
            'run_time_minute' => Carbon::now($this->timezone)->minute,
        ])->get();
    }
}
