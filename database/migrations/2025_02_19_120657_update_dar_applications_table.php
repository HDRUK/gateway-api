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
        Schema::table('dar_applications', function (Blueprint $table) {
            $table->dropIndex(['submission_status']);
            $table->dropColumn(['submission_status', 'approval_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dar_applications', function (Blueprint $table) {
            $table->string('submission_status')->nullable()->default('DRAFT');
            $table->string('approval_status')->nullable();
        });
    }
};
