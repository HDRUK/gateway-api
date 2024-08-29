<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('datasets_has_tool');
        Schema::enableForeignKeyConstraints();
    }
};
