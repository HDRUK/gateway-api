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
        Schema::create('entity_has_linkages', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('entity_id');
            $table->bigInteger('linked_entity_id');
            $table->string('entity_type', 255);
            $table->tinyInteger('direct_linkage');
            $table->enum('linkage_type', [
                'isDerivedFrom',
                'isPartOf',
                'isMemberOf',
                'linkedDatasets',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entity_has_linkages');
    }
};
