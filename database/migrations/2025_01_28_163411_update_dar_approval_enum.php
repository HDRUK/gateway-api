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
            $table->enum(
                'approval_status_temp',
                ['APPROVED','APPROVED_COMMENTS','REJECTED','WITHDRAWN']
            )
            ->nullable();
        });

        DB::statement('UPDATE dar_applications SET approval_status_temp = approval_status');

        Schema::table('dar_applications', function (Blueprint $table) {
            $table->dropColumn('approval_status');
        });

        Schema::table('dar_applications', function (Blueprint $table) {
            $table->renameColumn('approval_status_temp', 'approval_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dar_applications', function (Blueprint $table) {
            $table->enum(
                'approval_status_temp',
                ['APPROVED','APPROVED_COMMENTS','REJECTED']
            )
            ->nullable();
        });

        DB::statement('UPDATE dar_applications SET approval_status = NULL WHERE approval_status = "WITHDRAWN"');
        DB::statement('UPDATE dar_applications SET approval_status_temp = approval_status');

        Schema::table('dar_applications', function (Blueprint $table) {
            $table->dropColumn('approval_status');
        });

        Schema::table('dar_applications', function (Blueprint $table) {
            $table->renameColumn('approval_status_temp', 'approval_status');
        });
    }
};
