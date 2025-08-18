<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('collection_has_dataset_version', function (Blueprint $table) {
            $table->dropPrimary(['collection_id', 'dataset_version_id']);
        });

        Schema::table('collection_has_dataset_version', function (Blueprint $table) {
            $table->id()->first();
        });
    }

    public function down(): void
    {
        Schema::table('collection_has_dataset_version', function (Blueprint $table) {
            $table->dropColumn('id');
        });

        Schema::table('collection_has_dataset_version', function (Blueprint $table) {
            $table->primary(['collection_id', 'dataset_version_id']);
        });
    }
};
