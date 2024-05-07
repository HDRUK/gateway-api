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
        Schema::table('saved_searches', function (Blueprint $table) {
            $table->enum('sort_order', ['ASC', 'DESC'])->default('ASC');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('saved_searches', 'sort_order'))
        {
            Schema::table('saved_searches', function(Blueprint $table)
            {
                $table->dropColumn('sort_order');
            });
        }
    }
};
