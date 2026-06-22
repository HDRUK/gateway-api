<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('gwdm30_coverage', function (Blueprint $table) {
            $table->string('dataset_completeness', 2048)->nullable()->after('followup');
        });
    }

    public function down(): void
    {
        Schema::table('gwdm30_coverage', function (Blueprint $table) {
            $table->dropColumn('dataset_completeness');
        });
    }
};
