<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class DropDatasetsHasToolTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('datasets_has_tool');
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('dataset_has_tools', function (Blueprint $table) {
            $table->bigInteger('dataset_id')->unsigned();
            $table->bigInteger('tool_id')->unsigned();

            $table->primary(['dataset_id', 'tool_id']);
            $table->foreign('dataset_id')->references('id')->on('datasets')->onDelete('cascade');
            $table->foreign('tool_id')->references('id')->on('tools')->onDelete('cascade');

            $table->timestamps();
        });
    }
}
