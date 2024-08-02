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
            $table->boolean('is_dar_dialogue')->default(false)->after('unique_key');
            $table->boolean('is_dar_status')->default(false)->after('is_dar_dialogue');
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
