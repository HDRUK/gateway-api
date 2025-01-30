<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('enquiry_thread', function (Blueprint $table) {
            $table->tinyInteger('is_dar_review')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enquiry_thread', function (Blueprint $table) {
            $table->dropColumn('is_dar_review');
        });
    }
};
