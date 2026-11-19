<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        $openVersionIds = DB::table('dataset_versions as dv')
            ->join('datasets as d', 'd.id', '=', 'dv.dataset_id')
            ->where('d.status', 'DRAFT')
            ->whereNull('dv.deleted_at')
            ->whereRaw('dv.version = (
                select max(dv2.version) from dataset_versions as dv2
                where dv2.dataset_id = dv.dataset_id and dv2.deleted_at is null
            )')
            ->pluck('dv.id')
            ->all();

        DB::table('dataset_versions')
            ->whereNull('active_date')
            ->whereNotIn('id', $openVersionIds)
            ->orderBy('id')
            ->chunkById(1000, function ($versions) {
                DB::table('dataset_versions')
                    ->whereIn('id', $versions->pluck('id'))
                    ->update(['active_date' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)')]);
            });
    }

    public function down(): void
    {
    }
};
