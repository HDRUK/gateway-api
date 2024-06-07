<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('publication_has_dataset', function (Blueprint $table) {
            $table->renameColumn('link_type', 'link_type_old');
        });

        DB::statement("UPDATE publication_has_dataset SET link_type_old = 'USING' WHERE publication_has_dataset.link_type_old = 'UNKNOWN'");

        Schema::table('publication_has_dataset', function (Blueprint $table) {
            $table->enum('link_type', ['ABOUT', 'USING'])->default('USING');
        });
        DB::statement("UPDATE publication_has_dataset SET link_type = link_type_old");

        Schema::table('publication_has_dataset', function (Blueprint $table) {
            $table->dropColumn('link_type_old');
        });        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publication_has_dataset', function (Blueprint $table) {
            $table->enum('link_type', ['ABOUT', 'USING', 'UNKNOWN'])->default('UNKNOWN');
        });
    }
};
