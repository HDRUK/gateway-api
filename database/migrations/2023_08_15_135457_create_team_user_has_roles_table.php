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
        Schema::create('team_user_has_roles', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('team_has_user_id')->unsigned();
            $table->bigInteger('role_id')->unsigned();

            $table->foreign('team_has_user_id', 'team_has_user_id2_fk')->references('id')->on('team_has_users');
            $table->foreign('role_id', 'role_id_fk')->references('id')->on('roles');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_user_has_roles');
    }
};
