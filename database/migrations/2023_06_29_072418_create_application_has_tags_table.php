<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('application_has_tags', function (Blueprint $table) {
            $table->bigInteger('application_id')->unsigned();
            $table->bigInteger('tag_id')->unsigned();

            $table->foreign('application_id')->references('id')->on('applications');
            $table->foreign('tag_id')->references('id')->on('tags');

            $table->unique(['application_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_has_tags');
    }
};
