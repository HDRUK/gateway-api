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
        Schema::table('authorisation_codes', function (Blueprint $table) {
            $table->dropIndex('authorisation_codes_deleted_at_index');
        });

        Schema::dropIfExists('authorisation_codes');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('authorisation_codes')) {
            Schema::create('authorisation_codes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->text('jwt');
                $table->timestamps();
                $table->softDeletes();
                $table->index('deleted_at');
            });
        }
    }
};
