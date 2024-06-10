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

        if (Schema::hasTable('datasets_has_tool')) {
            Schema::drop('datasets_has_tool');
        }

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // If you need to restore the table, you'll have to recreate it
        // Define the structure here if necessary
    }
}
