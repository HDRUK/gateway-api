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
        Schema::create('tool_has_type_category', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('tool_id')->unsigned();
            $table->bigInteger('type_category_id')->unsigned();

            $table->foreign('tool_id')->references('id')->on('tools');
            $table->foreign('type_category_id')->references('id')->on('type_categories');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tool_has_type_category');
    }
};
