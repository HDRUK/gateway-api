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
        Schema::table('enquiry_thread', function (Blueprint $table) {
            $table->boolean('is_general_enquiry')->default(false);
            $table->boolean('is_feasibility_enquiry')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enquiry_thread', function (Blueprint $table) {
            $table->dropColumn('is_dar_dialogue');
            $table->dropColumn('is_dar_status');
        });
    }
};
