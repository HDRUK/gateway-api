<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('libraries', function (Blueprint $table) {
            $table->bigInteger('user_id')->unsigned()->nullable()->change();
            $table->bigInteger('dataset_id')->unsigned()->nullable()->change();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('dataset_id')->references('id')->on('datasets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('libraries', function (Blueprint $table) {
            $table->bigInteger('old_dataset_id')->nullable();
            $table->bigInteger('old_user_id')->nullable();
        });

        Schema::table('libraries', function (Blueprint $table) {
            DB::statement("UPDATE libraries SET old_user_id = user_id");
            DB::statement("UPDATE libraries SET old_dataset_id = dataset_id");
        });

        Schema::table('libraries', function (Blueprint $table) {
            $table->dropForeign(['dataset_id']);
            $table->dropColumn(['dataset_id']);
        });

        Schema::table('libraries', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id']);
        });

        Schema::table('libraries', function (Blueprint $table) {
            $table->renameColumn('old_dataset_id', 'dataset_id');
        });

        Schema::table('libraries', function (Blueprint $table) {
            $table->renameColumn('old_user_id', 'user_id');
        });
    }
};
