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
        Schema::create('tools', function (Blueprint $table) {
            $table->id();
            $table->char('mongo_object_id', 24)->nullable();
            $table->char('name', 45)->nullable();
            $table->char('url', 255)->nullable();
            $table->char('description', 255)->nullable();
            $table->char('license', 45)->nullable();
            $table->char('tech_stack', 45)->nullable();
            $table->bigInteger('user_id')->unsigned();
            $table->boolean('enabled')->nullable(true);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tools');
    }
};
