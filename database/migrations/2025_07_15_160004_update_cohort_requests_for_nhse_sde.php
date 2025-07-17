<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\CohortRequest;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cohort_requests', function (Blueprint $table) {
            $table->dropColumn(['is_nhse_sde_approval', 'cohort_status']);
            $table->string('nhse_sde_request_status', 50)->nullable()->default(null);
            $table->timestamp('nhse_sde_requested_at')->nullable()->default(null);
            $table->timestamp('nhse_sde_self_declared_approved_at')->nullable()->default(null);
            $table->timestamp('nhse_sde_request_expire_at')->nullable()->default(null);
            $table->timestamp('nhse_sde_updated_at')->nullable()->default(null);
        });

        Schema::table('cohort_request_logs', function (Blueprint $table) {
            $table->dropColumn('is_nhse_sde_approval');
            $table->string('nhse_sde_request_status', 50)->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cohort_requests', function (Blueprint $table) {
            $table->dropColumn(
                [
                    'nhse_sde_updated_at',
                    'nhse_sde_request_expire_at',
                    'nhse_sde_self_declared_approved_at',
                    'nhse_sde_requested_at',
                    'nhse_sde_request_status'
                ]
            );
            $table->boolean('cohort_status')->default(false)->after('request_status');
            $table->boolean('is_nhse_sde_approval')->default(false)->after('cohort_status');
        });

        $cohorts = CohortRequest::whereIn('request_status', ['APPROVED', 'REJECTED', 'SUSPENDED'])->get();
        foreach ($cohorts as $cohort) {
            CohortRequest::withoutTimestamps(fn () => $cohort->update(['cohort_status' => 1]));
        }

        Schema::table('cohort_request_logs', function (Blueprint $table) {
            $table->dropColumn('nhse_sde_request_status');
            $table->boolean('is_nhse_sde_approval')->default(false)->after('request_status');
        });
    }
};
