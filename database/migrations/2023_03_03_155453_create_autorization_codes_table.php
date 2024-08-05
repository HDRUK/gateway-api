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
        Schema::create('autorization_codes', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->default(0);
            $table->text('jwt')->nullable();
            $table->timestamp('create_at')->nullable();
            $table->timestamp('expire_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('autorization_codes');
    }
};
