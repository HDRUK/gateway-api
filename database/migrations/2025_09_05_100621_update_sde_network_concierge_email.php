<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('teams')
            ->where('name', 'SDE Network Concierge')
            ->update(['contact_point' => 'england.data.healthresearch@nhs.net']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('teams')
            ->where('name', 'SDE Network Concierge')
            ->update(['contact_point' => 'data.healthresearch@nhs.net']);
    }
};
