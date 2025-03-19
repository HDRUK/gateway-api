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
        Schema::create('dar_template_has_files', function (Blueprint $table) {
            $table->bigInteger('template_id')->unsigned();
            $table->bigInteger('upload_id')->unsigned();

            $table->foreign('template_id')->references('id')->on('dar_templates');
            $table->foreign('upload_id')->references('id')->on('uploads');

            $table->unique(['template_id', 'upload_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dar_template_has_files');
    }
};
