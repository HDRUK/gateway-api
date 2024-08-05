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
        Schema::create('publication_has_tools', function (Blueprint $table) {
            $table->bigInteger('publication_id')->unsigned();
            $table->bigInteger('tool_id')->unsigned();
            $table->bigInteger('user_id')->nullable()->default(null)->unsigned();
            $table->bigInteger('application_id')->nullable()->default(null)->unsigned();
            $table->text('reason')->nullable();

            $table->foreign('publication_id')->references('id')->on('publications');
            $table->foreign('tool_id')->references('id')->on('tools');
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
        Schema::dropIfExists('publication_has_tools');
    }
};
