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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->string('name', 45);
            $table->boolean('enabled')->default(1);
            $table->boolean('allows_messaging')->default(0);
            $table->boolean('workflow_enabled')->default(0);
            $table->boolean('access_requests_management')->default(0);
            $table->boolean('uses_5_safes')->default(0);
            $table->boolean('is_admin')->default(0);
            $table->integer('member_of');
            $table->string('contact_point', 128);
            $table->string('application_form_updated_by', 128);
            $table->dateTime('application_form_updated_on');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
