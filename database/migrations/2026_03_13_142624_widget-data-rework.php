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
        Schema::dropIfExists('widget_settings');

        Schema::table('widgets', function (Blueprint $table) {
            $table->dropColumn('colours');

            $table->string('branding_primary', 7)->nullable();
            $table->string('branding_secondary', 7)->nullable();
            $table->string('branding_neutral', 7)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nope - One way...
    }
};
