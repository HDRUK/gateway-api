<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('dataset_versions', function (Blueprint $table) {
            $table->string('gwdm_version', 20)->default('2.2')->change();
        });
    }

    public function down(): void
    {
        Schema::table('dataset_versions', function (Blueprint $table) {
            $table->string('gwdm_version', 20)->default('2.0')->change();
        });
    }
};
