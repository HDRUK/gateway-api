<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill gwdm30_distributions from existing gwdm30_accessibility.access_service values.
 *
 * For every GWDM 3.0 dataset version that already has an access_service URL stored,
 * create a corresponding Distribution row so that existing metadata is immediately
 * representable as a dcat:Distribution without requiring a re-ingest.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('gwdm30_accessibility')
            ->whereNotNull('access_service')
            ->where('access_service', '!=', '')
            ->orderBy('dataset_version_id')
            ->each(function (object $row) {
                $alreadyExists = DB::table('gwdm30_distributions')
                    ->where('dataset_version_id', $row->dataset_version_id)
                    ->exists();

                if (! $alreadyExists) {
                    DB::table('gwdm30_distributions')->insert([
                        'dataset_version_id' => $row->dataset_version_id,
                        'access_url' => $row->access_service,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Removing backfilled rows is safe — any row added by this migration
        // has a null title/download_url/media_type, distinguishing it from
        // rows added by hand after this migration ran.
        DB::table('gwdm30_distributions')
            ->whereNull('title')
            ->whereNull('download_url')
            ->whereNull('media_type')
            ->delete();
    }
};
