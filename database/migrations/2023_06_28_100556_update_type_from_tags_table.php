<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->char('type', 255)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tags')) {
            if (Schema::hasColumn('tags', 'type')) {
                DB::table('tags')->update(['type' => 'features']);
                DB::statement("ALTER TABLE `tags` MODIFY `type` ENUM('features', 'topics')");
            }
        }
    }
};
