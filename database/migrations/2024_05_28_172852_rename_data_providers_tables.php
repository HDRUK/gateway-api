<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('data_providers', 'data_provider_colls');
        Schema::rename('data_provider_has_teams', 'data_provider_coll_has_teams');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('data_provider_colls', 'data_providers');
        Schema::rename('data_provider_coll_has_teams', 'data_provider_has_teams');
    }
};
