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
        Schema::table('named_entities', function (Blueprint $table) {
            $table->text('name')->change(); // Change the column type to text
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('named_entities', function (Blueprint $table) {
            $table->char('name', 255)->change(); // Revert the column back to char(255)
        });
    }

};
