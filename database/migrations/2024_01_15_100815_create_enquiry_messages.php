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
        Schema::create('enquiry_messages', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->bigInteger('thread_id')->unsigned();
            $table->string('from', 255);
            $table->longText('message_body');

            $table->foreign('thread_id')->references('id')->on('enquiry_thread');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enquiry_messages');
    }
};
