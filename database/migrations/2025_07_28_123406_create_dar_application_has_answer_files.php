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
        Schema::create('dar_application_has_answer_files', function (Blueprint $table) {
            $table->unsignedBigInteger('application_file_id');
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('upload_id');

            $table->foreign('application_file_id')->references('id')->on('dar_application_has_files');
            $table->foreign('application_id')->references('id')->on('dar_applications');
            $table->foreign('upload_id')->references('id')->on('uploads');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dar_application_has_answer_files');
    }
};
