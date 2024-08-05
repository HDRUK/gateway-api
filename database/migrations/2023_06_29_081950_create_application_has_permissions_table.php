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
        Schema::create('application_has_permissions', function (Blueprint $table) {
            $table->bigInteger('application_id')->unsigned();
            $table->bigInteger('permission_id')->unsigned();

            $table->foreign('application_id')->references('id')->on('applications');
            $table->foreign('permission_id')->references('id')->on('permissions');

            $table->unique(['application_id', 'permission_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_has_permissions');
    }
};
