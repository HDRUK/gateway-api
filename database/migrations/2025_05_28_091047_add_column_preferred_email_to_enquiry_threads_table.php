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
        Schema::table('enquiry_threads', function (Blueprint $table) {
            $table->enum('user_preferred_email', ['primary', 'secondary'])->default('primary')->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enquiry_threads', function (Blueprint $table) {
            $table->dropColumn([
                'user_preferred_email',
            ]);
        });
    }
};
