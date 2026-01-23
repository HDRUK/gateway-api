<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public static string $cdsFlagName = 'CohortDiscoveryService';

    public static string $rquestFlagName = 'RQuest';

    public function up(): void
    {
        if (! DB::table('feature_flags')->where('key', self::$cdsFlagName)->exists()) {
            DB::table('feature_flags')->insert([
                'key' => self::$cdsFlagName,
                'enabled' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        if (! DB::table('feature_flags')->where('key', self::$rquestFlagName)->exists()) {
            DB::table('feature_flags')->insert([
                'key' => self::$rquestFlagName,
                'enabled' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('feature_flags')
            ->where('key', self::$cdsFlagName)
            ->orWhere('key', self::$rquestFlagName)
            ->delete();
    }
};
