<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * gwdm30_required — one row per dataset version.
 *
 * Stores the GWDM 3.0 `required` block: system identifiers, issued/modified
 * datetimes, the semver version string, and the revisions audit log (JSON).
 * The blob continues to hold this data; SQL adds queryability.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('gwdm30_required', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_version_id')
                ->constrained('dataset_versions')
                ->cascadeOnDelete();
            $table->string('gateway_id', 50)->nullable();
            $table->string('gateway_pid', 50)->nullable();
            $table->dateTime('issued')->nullable();
            $table->dateTime('modified')->nullable();
            $table->string('version', 20)->nullable();
            // Array of {version: string, url: string|null} revision objects
            $table->json('revisions')->nullable();
            $table->unique('dataset_version_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gwdm30_required');
    }
};
