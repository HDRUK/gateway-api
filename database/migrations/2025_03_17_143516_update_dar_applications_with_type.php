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
        Schema::table('dar_applications', function (Blueprint $table) {
            $table->string('application_type')->default('FORM');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::table('dar_application_has_dataset', function (Blueprint $table) {
        //     $table->dropForeign('dar_application_has_dataset_dar_application_id_foreign');
        // });

        // Schema::table('dar_application_reviews', function (Blueprint $table) {
        //     $table->dropForeign('dar_application_reviews_application_id_foreign');
        // });

        // Schema::table('dar_application_statuses', function (Blueprint $table) {
        //     $table->dropForeign('dar_application_statuses_application_id_foreign');
        // });

        Schema::table('dar_applications', function (Blueprint $table) {
            $table->dropColumn('application_type');
        });
    }
};
