<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Pennant\Migrations\PennantMigration;

return new class extends PennantMigration
{
    public function up(): void
    {
        Schema::table('features', function (Blueprint $table) {
            $table->dropUnique(['name']);

            $table->dropColumn(['enabled', 'deleted_at']);

            $table->string('name')->nullable(false)->change();

            $table->string('scope')->default('global')->after('name');
            $table->text('value')->after('scope');

            $table->unique(['name', 'scope']);
        });
    }

    public function down(): void
    {
        Schema::table('features', function (Blueprint $table) {
            $table->dropUnique(['name', 'scope']);

            $table->boolean('enabled')->default(true);
            $table->softDeletes();

            $table->dropColumn(['scope', 'value']);

            $table->string('name')->nullable()->change();

            $table->unique('name');
        });
    }
};
