<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('saved_searches', function (Blueprint $table) {
            $table->enum('sort_order', ['score','title_asc','title_desc','updated_at_desc', 'updated_at_asc'])->default('score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('saved_searches', 'sort_order')) {
            Schema::table('saved_searches', function (Blueprint $table) {
                $table->dropColumn('sort_order');
            });
        }
    }
};
