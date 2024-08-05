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
        Schema::create('dar_applications', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->bigInteger('applicant_id'); // user_id
            $table->enum('submission_status', ['DRAFT', 'SUBMITTED', 'FEEDBACK'])->default('DRAFT');
            $table->enum('approval_status', ['APPROVED', 'APPROVED_COMMENTS', 'REJECTED'])->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dar_applications');
    }
};
