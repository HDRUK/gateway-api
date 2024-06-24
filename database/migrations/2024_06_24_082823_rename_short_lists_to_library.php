<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameShortListsToLibrary extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::rename('short_lists', 'library');
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::rename('library', 'short_lists');
    }
}

