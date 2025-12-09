<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('feature_flags')->where('key', 'Widgets')->exists()) {
            DB::table('feature_flags')->insert([
                'key'        => 'Widgets',
                'enabled'    => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('feature_flags')
            ->where('key', 'Widgets')
            ->delete();
    }
};
