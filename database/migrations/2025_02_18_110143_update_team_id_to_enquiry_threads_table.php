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
            $table->integer('team_id')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enquiry_threads', function (Blueprint $table) {
            $table->integer('my_column')->nullable(false)->default(0)->change();
        });
    }
};
