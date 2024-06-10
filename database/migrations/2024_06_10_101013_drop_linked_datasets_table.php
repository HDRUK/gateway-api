<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class DropLinkedDatasetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        if (Schema::hasTable('linked_datasets')) {
            Schema::drop('linked_datasets');
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
