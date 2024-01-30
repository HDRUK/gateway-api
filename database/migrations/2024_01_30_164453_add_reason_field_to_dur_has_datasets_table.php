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
        Schema::table('dur_has_datasets', function (Blueprint $table) {
            $table->string('reason')->nullable()->after('is_locked'); // relatedObjects.reason
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dur_has_datasets', function (Blueprint $table) {
            $table->dropColumn('reason');
        });
    }
};
