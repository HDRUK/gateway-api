<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Make both linkage junction tables the complete source of truth for linkage data.
 *
 * Previously, LinkageExtraction skipped any reference it could not resolve to a known
 * dataset or publication. This meant unresolvable free-text titles and DOIs only lived
 * in the JSON metadata blob, requiring a dual-source merge on every read.
 *
 * After this migration:
 *   - dataset_version_has_dataset_version stores unresolved dataset references as rows
 *     with dataset_version_target_id = NULL and the raw url/pid/title preserved.
 *   - publication_has_dataset_version stores unresolved publication references as rows
 *     with publication_id = NULL and the raw DOI preserved.
 *   - Gwdm2xHandler::afterRead() can reconstruct the full GWDM linkage section from SQL.
 *
 * NOTE: Existing rows that were extracted before this migration contain only resolved
 * references. Re-dispatching LinkageExtraction for those dataset versions will backfill
 * any unresolved references that were previously discarded.
 */
return new class () extends Migration {
    public function up(): void
    {
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        Schema::table('dataset_version_has_dataset_version', function (Blueprint $table) use ($isSqlite) {
            // SQLite rebuilds the whole table on ALTER — named FK drop is not supported.
            if (!$isSqlite) {
                $table->dropForeign('ld_dataset_version_target_id_fk');
            }

            $table->unsignedBigInteger('dataset_version_target_id')->nullable()->change();

            // Raw reference fields for unresolved linkages (target_id = NULL rows).
            $table->string('raw_url', 2048)->nullable()->after('dataset_version_target_id');
            $table->string('raw_pid', 255)->nullable()->after('raw_url');
            // text(): free text pulled from external, unvalidated linkage references —
            // unlike the internal title/short_title columns there's no app-side bound
            // on length, and a VARCHAR cap risks a strict-mode INSERT failure.
            $table->text('raw_title')->nullable()->after('raw_pid');

            // Re-add FK as nullable (NULL = unresolved, non-NULL = resolved).
            if (!$isSqlite) {
                $table->foreign('dataset_version_target_id', 'ld_dataset_version_target_id_fk')
                    ->references('id')->on('dataset_versions')
                    ->onDelete('cascade');
            }
        });

        Schema::table('publication_has_dataset_version', function (Blueprint $table) {
            $table->dropForeign(['publication_id']);

            $table->unsignedBigInteger('publication_id')->nullable()->change();

            $table->string('raw_doi', 2048)->nullable()->after('publication_id');

            $table->foreign('publication_id')
                ->references('id')->on('publications')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        Schema::table('dataset_version_has_dataset_version', function (Blueprint $table) use ($isSqlite) {
            if (!$isSqlite) {
                $table->dropForeign('ld_dataset_version_target_id_fk');
            }
            $table->dropColumn(['raw_url', 'raw_pid', 'raw_title']);
            DB::table('dataset_version_has_dataset_version')
                ->whereNull('dataset_version_target_id')
                ->delete();
            $table->unsignedBigInteger('dataset_version_target_id')->nullable(false)->change();
            if (!$isSqlite) {
                $table->foreign('dataset_version_target_id', 'ld_dataset_version_target_id_fk')
                    ->references('id')->on('dataset_versions')
                    ->onDelete('cascade');
            }
        });

        Schema::table('publication_has_dataset_version', function (Blueprint $table) {
            $table->dropForeign(['publication_id']);
            $table->dropColumn('raw_doi');
            \DB::table('publication_has_dataset_version')
                ->whereNull('publication_id')
                ->delete();
            $table->unsignedBigInteger('publication_id')->nullable(false)->change();
            $table->foreign('publication_id')
                ->references('id')->on('publications')
                ->onDelete('cascade');
        });
    }
};
