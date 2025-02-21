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
        Schema::create('team_has_dar_applications', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->bigInteger('dar_application_id')->unsigned();
            $table->bigInteger('team_id')->unsigned();

            $table->foreign('dar_application_id')->references('id')->on('dar_applications');
            $table->foreign('team_id')->references('id')->on('teams');

            $table->unique(['dar_application_id', 'team_id']);
            $table->index('dar_application_id');
            $table->index('team_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_has_dar_applications');
    }
};
