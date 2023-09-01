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
        Schema::table('authorisation_codes', function (Blueprint $table) {
            $table->dropColumn(['create_at', 'expire_at', 'updated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('authorisation_codes', function (Blueprint $table) {
            $table->timestamp('create_at')->nullable();
            $table->timestamp('expire_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });
    }
};
