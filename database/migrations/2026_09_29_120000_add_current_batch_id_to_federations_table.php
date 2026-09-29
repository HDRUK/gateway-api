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
        Schema::table('federations', function (Blueprint $table) {
            $table->string('current_batch_id')->nullable()->after('is_running');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('federations', function (Blueprint $table) {
            $table->dropColumn('current_batch_id');
        });
    }
};
