<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('dataset_link_check_results', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id')->nullable()->after('dataset_id');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
            $table->string('team_name')->nullable()->after('team_id');

            $table->index('team_id');
        });
    }

    public function down(): void
    {
        Schema::table('dataset_link_check_results', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->dropColumn(['team_id', 'team_name']);
        });
    }
};
