<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('enquiry_thread', 'enquiry_threads');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('enquiry_threads', 'enquiry_thread');
    }
};
