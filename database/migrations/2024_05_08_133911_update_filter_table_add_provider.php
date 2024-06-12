<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('filters', function (Blueprint $table) {
            $table->dropUnique(['type', 'keys']);
            $table->renameColumn('type', 'type_old');
        });

        Schema::table('filters', function (Blueprint $table) {
            $table->enum('type', \Config::get('filters.types'));
        });

        DB::statement("UPDATE filters SET type = type_old");

        Schema::table('filters', function (Blueprint $table) {
            $table->dropColumn('type_old');
            $table->unique(['type', 'keys']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Schema::table('filters', function (Blueprint $table) {
            $table->dropUnique(['type', 'keys']);
            $table->enum('type_old', [
                'dataset',
                'collection',
                'tool',
                'course',
                'project',
                'paper',
                'dataUseRegister',
            ]);
        });

        // Delete rows where type is dataProvider
        DB::table('filters')->where('type', 'dataProvider')->delete();

        // Handle potential duplicates before altering the table
        $duplicates = DB::table('filters')
            ->select('type', 'keys', DB::raw('COUNT(*) as count'))
            ->groupBy('type', 'keys')
            ->having('count', '>', 1)
            ->get();

        foreach ($duplicates as $duplicate) {
            $ids = DB::table('filters')
                ->where('type', $duplicate->type)
                ->where('keys', $duplicate->keys)
                ->orderBy('id', 'asc')
                ->pluck('id')
                ->toArray();

            // Remove all but the first occurrence
            DB::table('filters')->whereIn('id', array_slice($ids, 1))->delete();
        }

        DB::statement("UPDATE filters SET type_old = type WHERE filters.type != 'dataProvider'");

        Schema::table('filters', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('filters', function (Blueprint $table) {
            $table->renameColumn('type_old', 'type');
            $table->unique(['type', 'keys']);
        });

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
};
