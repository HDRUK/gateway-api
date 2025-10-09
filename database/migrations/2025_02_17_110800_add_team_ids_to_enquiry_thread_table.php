<?php

use App\Models\EnquiryThread;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('enquiry_threads', function (Blueprint $table) {
            $table->json('team_ids')->after('user_id');
        });


        $equiryTreads = \DB::SELECT('SELECT id, team_id FROM enquiry_threads');
        foreach ($equiryTreads as $equiryTread) {
            $teamIds = [];
            $teamIds[] = $equiryTread->team_id;

            EnquiryThread::where('id', $equiryTread->id)
                ->update([
                    'team_ids' => $teamIds,
                ]);
        }

        if (Schema::hasTable('enquiry_threads')) {
            Schema::table('enquiry_threads', function (Blueprint $table) {
                $table->dropIndex('enquiry_thread_team_id_index');
                $table->dropColumn('team_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $equiryTreads = \DB::SELECT('SELECT id, team_ids FROM enquiry_threads');
        foreach ($equiryTreads as $equiryTread) {
            $teamId = $equiryTread->team_ids;

            EnquiryThread::where('id', $equiryTread->id)
                ->update([
                    'team_id' => $teamId[0],
                ]);
        }

        Schema::table('enquiry_threads', function (Blueprint $table) {
            $table->dropColumn('team_ids');
        });
    }
};
