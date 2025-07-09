<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use App\Models\User;
use App\Models\Team;
use App\Models\TeamHasUser;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $adminIds = User::where('is_admin', true)->pluck('id')->toArray();
        $teams = Team::where([
            'enabled' => 1,
            'deleted_at' => null,
        ])->pluck('id')->toArray();

        foreach ($adminIds as $adminId) {
            foreach ($teams as $team) {
                TeamHasUser::firstOrCreate([
                    'user_id' => $adminId,
                    'team_id' => $team,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nope. One way only.
    }
};
