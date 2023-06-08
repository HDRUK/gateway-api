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
        Schema::table('users', function (Blueprint $table) {
            $table->string('firstname')->nullable(true)->after('name');
            $table->string('lastname')->nullable(true)->after('firstname');
            $table->string('providerid')->nullable(true)->after('password');
            $table->string('provider')->nullable(true)->after('providerid');
            $table->string('password')->nullable(true)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['firstname', 'lastname', 'providerid', 'provider']);
            $table->string('password')->nullable(false)->change();
        });
    }
};
