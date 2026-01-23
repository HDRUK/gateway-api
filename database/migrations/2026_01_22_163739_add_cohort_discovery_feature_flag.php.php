<?php

use App\Models\FeatureFlag;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('feature_flags')->where('key', FeatureFlag::KEY_COHORT_DISCOVERY_SERVICE
        )->exists()) {
            DB::table('feature_flags')->insert([
                'key' => FeatureFlag::KEY_COHORT_DISCOVERY_SERVICE,
                'enabled' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        if (! DB::table('feature_flags')->where('key', FeatureFlag::KEY_RQUEST)->exists()) {
            DB::table('feature_flags')->insert([
                'key' => FeatureFlag::KEY_RQUEST,
                'enabled' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('feature_flags')
            ->where('key', FeatureFlag::KEY_COHORT_DISCOVERY_SERVICE)
            ->orWhere('key', FeatureFlag::KEY_RQUEST)
            ->delete();
    }
};
