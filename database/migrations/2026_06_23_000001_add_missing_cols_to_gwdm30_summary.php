<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('gwdm30_summary', function (Blueprint $table) {
            $table->string('dataset_sub_type', 100)->nullable()->after('dataset_type');
            $table->string('in_pipeline', 50)->nullable()->after('dataset_sub_type');
            $table->text('funders')->nullable()->after('in_pipeline');
            $table->string('publisher_ror_id', 9)->nullable()->after('publisher_gateway_id');
        });
    }

    public function down(): void
    {
        Schema::table('gwdm30_summary', function (Blueprint $table) {
            $table->dropColumn(['dataset_sub_type', 'in_pipeline', 'funders', 'publisher_ror_id']);
        });
    }
};
