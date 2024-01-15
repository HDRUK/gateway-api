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
        Schema::create('enquiry_thread', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('team_id');
            $table->string('unique_key',64)->unique();
            $table->string('project_title',255);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enquiry_thread');
    }
};
