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
        Schema::table('dataset_versions', function (Blueprint $table) {
            $table->dateTime('active_date')->nullable()->after('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dataset_versions', function (Blueprint $table) {
            $table->dropColumn('active_date');
        });
    }
};
