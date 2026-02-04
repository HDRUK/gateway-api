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
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('dataset_versions', function (Blueprint $table) {
                $table->string('title', 500)
                      ->nullable()
                      ->default(null)
                      ->storedAs("CASE WHEN LEFT(metadata, 1) = '{' THEN JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.metadata.summary.title')) ELSE JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(metadata), '$.metadata.summary.title')) END")
                      ->collation('utf8mb4_general_ci');
            });
            DB::statement('ALTER TABLE dataset_versions ADD INDEX idx_title (title(500))');
        } else {
            Schema::table('dataset_versions', function (Blueprint $table) {
                $table->string('title')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dataset_versions', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->dropIndex(['idx_title']);
                $table->dropColumn('title');
            } else {
                $table->dropColumn('title');
            }
        });
    }
};
