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
        Schema::table('dur', function (Blueprint $table) {
            $table->string('applicant_id')->nullable(); // applicantId
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('dur')) {
            Schema::table('dur', function (Blueprint $table) {
                $table->dropColumn('applicant_id');
            });
        }
    }
};
