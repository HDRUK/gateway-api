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
        Schema::create('enquiry_thread_has_datasets', function (Blueprint $table) {
            $table->bigInteger('enquiry_thread_id');
            $table->bigInteger('dataset_id');
            $table->enum('interest_type', ['PRIMARY', 'SECONDARY']); // Determines primary Dataset interest or secondary
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enquiry_thread_has_datasets');
    }
};
