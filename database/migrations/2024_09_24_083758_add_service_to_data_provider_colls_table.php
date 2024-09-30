<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('data_provider_colls', function (Blueprint $table) {
            $table->string('service')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('data_provider_colls', 'service')) {
            Schema::table('data_provider_colls', function (Blueprint $table) {
                $table->dropColumn([
                    'service',
                ]);
            });
        }
    }
};
