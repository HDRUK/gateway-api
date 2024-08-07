<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('data_provider_coll_has_teams', function (Blueprint $table) {
            $table->renameColumn('data_provider_id', 'data_provider_coll_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_provider_coll_has_teams', function (Blueprint $table) {
            $table->renameColumn('data_provider_coll_id', 'data_provider_id');
        });
    }
};
