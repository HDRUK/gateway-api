<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dar_applications', function (Blueprint $table) {
            $table->string('project_id')->default(0);
            $table->boolean('is_joint')->default(false);
            $table->string('approval_status')->nullable();
            $table->string('submission_status')->default('DRAFT');
            $table->bigInteger('status_review_id')->nullable();
        });

        DB::statement('UPDATE dar_applications SET project_id = id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dar_applications', function (Blueprint $table) {
            $table->dropColumn([
                'project_id',
                'is_joint',
                'status_review_id',
                'approval_status',
                'submission_status'
            ]);
        });
    }
};
