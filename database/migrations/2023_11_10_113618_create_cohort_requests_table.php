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
        Schema::create('cohort_requests', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('user_id')->unsigned();

            // PENDING / APPROVED / BANNED / SUSPENDED / EXPIRED
            $table->string('request_status', 20)->nullable();
            $table->boolean('cohort_status')->default(false); // access or denied
            $table->timestamp('request_expire_at')->nullable();
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
        Schema::dropIfExists('cohort_requests');
    }
};
