<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('gwdm30_accessibility', function (Blueprint $table) {
            $table->string('access_service_category')->nullable()->after('access_service');
        });
    }

    public function down(): void
    {
        Schema::table('gwdm30_accessibility', function (Blueprint $table) {
            $table->dropColumn('access_service_category');
        });
    }
};
