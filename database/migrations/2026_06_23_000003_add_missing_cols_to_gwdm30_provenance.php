<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('gwdm30_provenance', function (Blueprint $table) {
            $table->string('origin_image_contrast', 50)->nullable()->after('origin_collection_situation');
            $table->date('temporal_distribution_release_date')->nullable()->after('temporal_accrual_periodicity');
        });
    }

    public function down(): void
    {
        Schema::table('gwdm30_provenance', function (Blueprint $table) {
            $table->dropColumn(['origin_image_contrast', 'temporal_distribution_release_date']);
        });
    }
};
