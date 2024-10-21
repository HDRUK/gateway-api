<?php

use Illuminate\Support\Carbon;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('filters')->insert([
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'deleted_at' => null,
            'keys' => 'datasetSubType',
            'enabled' => 1,
            'type' => 'dataset',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('filters')->where([
            'keys' => 'datasetSubType',
            'enabled' => 1,
            'type' => 'dataset',
        ])->delete();
    }
};
