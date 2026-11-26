<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per enrichment module, subject and params (GAT-9844): the record of
 * the latest pull. Bulk source data (e.g. CLOSER variables) gets its own
 * tables; this holds status, version and a summary payload.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('module_enrichments', function (Blueprint $table) {
            $table->id();
            $table->string('module', 64);
            $table->morphs('enrichable');
            $table->json('params');
            $table->char('params_hash', 40);
            $table->string('partner', 32);
            $table->string('status', 16);
            $table->json('payload')->nullable();
            $table->string('source_version', 64)->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();

            $table->unique(['module', 'enrichable_type', 'enrichable_id', 'params_hash'], 'module_enrichments_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_enrichments');
    }
};
