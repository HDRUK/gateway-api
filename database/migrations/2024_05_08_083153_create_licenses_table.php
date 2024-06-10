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
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();

            $table->char('code');
            $table->char('label')->nullable();
            $table->timestamp('valid_since')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->text('definition')->nullable();
            $table->boolean('verified')->default(1);
            $table->char('origin')->default('HDR');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
