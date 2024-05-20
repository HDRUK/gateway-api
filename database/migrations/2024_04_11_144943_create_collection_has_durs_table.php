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
        Schema::create('collection_has_durs', function (Blueprint $table) {
            $table->bigInteger('collection_id')->unsigned();
            $table->bigInteger('dur_id')->unsigned();
            $table->bigInteger('user_id')->nullable()->default(null)->unsigned();
            $table->bigInteger('application_id')->nullable()->default(null)->unsigned();
            $table->text('reason')->nullable();

            $table->foreign('collection_id')->references('id')->on('collections');
            $table->foreign('dur_id')->references('id')->on('dur');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('application_id')->references('id')->on('applications');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_has_durs');
    }
};
