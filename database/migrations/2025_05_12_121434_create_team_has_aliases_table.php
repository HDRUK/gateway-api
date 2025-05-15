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
        Schema::create('team_has_aliases', function (Blueprint $table) {
            $table->bigInteger('team_id')->unsigned();
            $table->bigInteger('alias_id')->unsigned();

            $table->foreign('team_id', 'team_id_fk')->references('id')->on('teams');
            $table->foreign('alias_id', 'alias_id_fk')->references('id')->on('aliases');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_has_aliases');
    }
};
