<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('gwdm30_accessibility', function (Blueprint $table) {
            $table->string('resource_creator_gateway_id', 256)->nullable()->after('resource_creator');
            $table->string('resource_creator_ror_id', 256)->nullable()->after('resource_creator_gateway_id');
        });
    }

    public function down(): void
    {
        Schema::table('gwdm30_accessibility', function (Blueprint $table) {
            $table->dropColumn(['resource_creator_gateway_id', 'resource_creator_ror_id']);
        });
    }
};
