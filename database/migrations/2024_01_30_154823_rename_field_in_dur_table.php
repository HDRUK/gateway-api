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
        Schema::table('dur', function (Blueprint $table) {
            $table->renameColumn('confidential_description', 'confidential_data_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('dur')) {
            Schema::table('dur', function (Blueprint $table) {
                $table->renameColumn('confidential_data_description', 'confidential_description');
            });
        }
    }
};
