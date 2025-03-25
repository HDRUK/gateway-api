<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('dataset_versions', function (Blueprint $table) {
                $table->string('short_title')
                      ->storedAs("CASE WHEN LEFT(metadata, 1) = '{' THEN JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.metadata.summary.shortTitle')) ELSE JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(metadata), '$.metadata.summary.shortTitle')) END")
                      ->collation('utf8mb4_general_ci');

                $table->index('short_title');
            });
        } else {
            Schema::table('dataset_versions', function (Blueprint $table) {
                $table->string('short_title')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dataset_versions', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->dropIndex(['short_title']);
                $table->dropColumn('short_title');
            } else {
                $table->dropColumn('short_title');
            }
        });
    }
};
