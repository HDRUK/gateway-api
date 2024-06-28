<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameShortListsToLibraries extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::rename('short_lists', 'libraries');
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::rename('libraries', 'short_lists');
    }
}

