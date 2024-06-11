<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dataset_versions', function (Blueprint $table) {
            $table->bigInteger('provider_team_id')->unsigned()->nullable();
            $table->string('application_type')->nullable();
            $table->foreign('provider_team_id')->references('id')->on('teams');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::table('dataset_versions', function (Blueprint $table) {
            
            $table->dropForeign(['provider_team_id']);
            $table->dropColumn('provider_team_id');
            $table->dropColumn('application_type');
        });
        Schema::enableForeignKeyConstraints();
    }
};
