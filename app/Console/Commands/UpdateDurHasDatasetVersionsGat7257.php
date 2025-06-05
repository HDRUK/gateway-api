<?php

namespace App\Console\Commands;

use App\Models\DurHasDatasetVersion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class UpdateDurHasDatasetVersionsGat7257 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-dur-has-dataset-versions-gat7257';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command for task GAT-7257: Multiple entries for same link in dur_has_dataset_versions table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('tmp_dur_has_dataset_version', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('dur_id')->unsigned();
            $table->bigInteger('dataset_version_id')->unsigned();
            $table->bigInteger('user_id')->unsigned()->nullable();
            $table->bigInteger('application_id')->unsigned()->nullable();
            $table->boolean('is_locked')->default(false);
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DurHasDatasetVersion::onlyTrashed()->forceDelete();

        DB::table('tmp_dur_has_dataset_version')->insert(
            DB::table('dur_has_dataset_version')
                ->select('dur_id', 'dataset_version_id', 'user_id', 'application_id', 'is_locked', 'reason', 'created_at', 'updated_at', 'deleted_at')
                ->get()
                ->unique(fn ($row) => $row->dur_id . '-' . $row->dataset_version_id)
                ->map(fn ($row) => (array)$row)
                ->toArray()
        );

        Schema::drop('dur_has_dataset_version');

        Schema::create('dur_has_dataset_version', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('dur_id')->unsigned();
            $table->bigInteger('dataset_version_id')->unsigned();
            $table->bigInteger('user_id')->unsigned()->nullable();
            $table->bigInteger('application_id')->unsigned()->nullable();
            $table->boolean('is_locked')->default(false);
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('dur_id')->references('id')->on('dur');
            $table->foreign('dataset_version_id')->references('id')->on('dataset_versions');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('application_id')->references('id')->on('applications');
        });

        DB::table('dur_has_dataset_version')->insert(
            DB::table('tmp_dur_has_dataset_version')
                ->select('dur_id', 'dataset_version_id', 'user_id', 'application_id', 'is_locked', 'reason', 'created_at', 'updated_at', 'deleted_at')
                ->get()
                ->map(fn ($row) => (array)$row)
                ->toArray()
        );

        Schema::drop('tmp_dur_has_dataset_version');

        Schema::enableForeignKeyConstraints();

        $this->info('DurHasDatasetVersion table updated successfully, duplicates removed.');
        return 0;
    }
}
