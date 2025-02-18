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
        Schema::table('enquiry_thread', function (Blueprint $table) {
            $table->json('team_ids')->after('team_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enquiry_thread', function (Blueprint $table) {
            $table->dropColumn('team_ids');
        });
    }
};
