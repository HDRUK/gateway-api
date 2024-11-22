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
        // Schema::table('dar_app_q_has_enq_threads', function (Blueprint $table) {
        //     $table->dropIndex('dar_app_q_has_enq_threads_equiry_thread_id_index');
        // });
        Schema::table('dar_app_q_has_enq_threads', function (Blueprint $table) {
            $table->renameColumn('equiry_thread_id', 'enquiry_thread_id');
            $table->renameIndex('dar_app_q_has_enq_threads_equiry_thread_id_index', 'dar_app_q_has_enq_threads_enquiry_thread_id_index');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dar_app_q_has_enq_threads', function (Blueprint $table) {
            $table->renameColumn('enquiry_thread_id', 'equiry_thread_id');
            $table->renameIndex('dar_app_q_has_enq_threads_enquiry_thread_id_index', 'dar_app_q_has_enq_threads_equiry_thread_id_index');
        });
    }
};
