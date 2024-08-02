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
        Schema::table('collection_has_datasets', function (Blueprint $table) {
            $table->text('reason')->nullable()->after('application_id'); // relatedObjects.reason
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collection_has_datasets', function (Blueprint $table) {
            $table->dropColumn('reason');
        });
    }
};
