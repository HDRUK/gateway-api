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
        Schema::table('dur_has_datasets', function (Blueprint $table) {
            $table->boolean('is_locked')->default(1)->after('application_id'); // relatedObjects.isLocked
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('dur_has_datasets')) {
            Schema::table('dur_has_datasets', function (Blueprint $table) {
                $table->dropColumn('is_locked');
            });
        }
    }
};

// isLocked - true/false
// reason - string
