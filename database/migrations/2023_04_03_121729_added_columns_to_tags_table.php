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
            $table->char('description')->after('type')->nullable(true)->default('')->change();
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
        Schema::disableForeignKeyConstraints();

        if (Schema::hasColumn('tags', 'deleted_at')) {
            Schema::table('tags', function (Blueprint $table) {
                $table->dropColumn([
                    'deleted_at',
                ]);
            });
        }

        if (Schema::hasColumn('tags', 'enabled')) {
            Schema::table('tags', function (Blueprint $table) {
                $table->dropColumn([
                    'enabled',
                ]);
            });
        }

        if (Schema::hasColumn('tags', 'description')) {
            Schema::table('tags', function (Blueprint $table) {
                $table->dropColumn([
                    'description',
                ]);
            });
        }

        Schema::disableForeignKeyConstraints();

        $foreignKeys = $this->listTableForeignKeys('filters');

        if (in_array('tags_type_unique', $foreignKeys)) {
            DB::statement('ALTER TABLE `tags` DROP FOREIGN KEY `tags_type_unique`');
        }

        Schema::enableForeignKeyConstraints();

        Schema::table('tags', function (Blueprint $table) {
            $table->char('description', 255)->nullable();
            $table->dropUnique('tags_type_unique');
            $table->enum('type', ['features', 'topics'])->change();
        });

    }

    public function listTableForeignKeys($table)
    {
        return array_map(function ($key) {
            return $key->getName();
        }, Schema::getForeignKeys($table));
    }
};
