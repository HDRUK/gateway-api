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
        Schema::table('tags', function (Blueprint $table) {
            $table->char('description', 255)->after('type')->nullable(true)->default('')->change();
            $table->softDeletes()->after('updated_at')->nullable(true);
            $table->boolean('enabled')->default(true)->after('deleted_at');
            $table->unique('type')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->dropColumn(['value', 'deleted_at', 'enabled']);
            $table->char('description', 255)->nullable();
            $table->dropIndex('tags_type_unique');
        });
    }
};
