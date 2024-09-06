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
            $table->text('reason')->nullable()->after('is_locked'); // relatedObjects.reason
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('dur_has_datasets')) {
            Schema::table('dur_has_datasets', function (Blueprint $table) {
                $table->dropColumn('reason');
            });
        }
    }
};
