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
        Schema::create('federations', function (Blueprint $table) {
            $table->id();

            $table->string('auth_type')->nullable(false);
            $table->string('auth_secret_key')->nullable(false);
            $table->string('endpoint_baseurl')->nullable(false);
            $table->string('endpoint_datasets')->nullable(false);
            $table->string('endpoint_dataset')->nullable(false);
            $table->tinyInteger('run_time_hour')->between(0, 23);
            $table->Integer('enabled')->default(1);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('federations');
    }
};
