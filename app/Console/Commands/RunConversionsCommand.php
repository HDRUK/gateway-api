<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class RunConversionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'conversion:run {--rollback} {--step=1 : Number of batches to rollback}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run all pending conversions or rollback batches (we can add number of steps to rollback)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->ensureConversionsTableExists();

        if ($this->option('rollback')) {
            $steps = (int) $this->option('step');
            $this->rollbackConversions($steps);
        } else {
            $this->runConversions();
        }

        return 0;
    }

    /**
     * Ensure that the conversions table exists.
     *
     * @return void
     */
    protected function ensureConversionsTableExists()
    {
        if (!DB::connection()->getSchemaBuilder()->hasTable('conversions')) {
            DB::connection()->getSchemaBuilder()->create('conversions', function ($table) {
                $table->increments('id');
                $table->string('conversion');
                $table->integer('batch');
                $table->timestamps();
            });
            $this->info('Created conversions table.');
        }
    }

    /**
     * Run all pending conversions.
     *
     * @return void
     */
    protected function runConversions()
    {
        $executed = DB::table('conversions')->pluck('conversion')->toArray();
        $lastBatch = DB::table('conversions')->max('batch');
        $batch = $lastBatch ? $lastBatch + 1 : 1;

        $files = File::glob(database_path('conversions') . '/*.php');

        // Sort files to ensure they run in order
        sort($files);

        foreach ($files as $file) {
            $fileName = basename($file, '.php');

            if (!in_array($fileName, $executed)) {
                $this->line("Running: {$fileName}");

                try {
                    $instance = include $file;

                    if (!is_object($instance)) {
                        $this->error("The file {$fileName}.php did not return a class instance.");
                        continue;
                    }

                    if (!method_exists($instance, 'up')) {
                        $this->error("Method 'up' does not exist in the conversion returned by {$fileName}.php");
                        continue;
                    }

                    $instance->up();

                    // If no exception occurred, consider it successful
                    DB::table('conversions')->insert([
                        'conversion' => $fileName,
                        'batch' => $batch,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $this->info("Completed: {$fileName}");
                } catch (\Exception $e) {
                    $this->error("Failed to run {$fileName}: {$e->getMessage()}");
                }
            }
        }
    }

    /**
     * Rollback the specified number of batches.
     *
     * @param  int  $steps
     * @return void
     */
    protected function rollbackConversions($steps)
    {
        $lastBatch = DB::table('conversions')->max('batch');

        if ($lastBatch === null) {
            $this->info('No conversions to rollback.');
            return;
        }

        $batches = DB::table('conversions')
            ->select('batch')
            ->distinct()
            ->orderBy('batch', 'desc')
            ->limit($steps)
            ->pluck('batch')
            ->toArray();

        if (empty($batches)) {
            $this->info('No conversions to rollback.');
            return;
        }

        foreach ($batches as $batch) {
            $conversions = DB::table('conversions')
                ->where('batch', $batch)
                ->orderBy('id', 'desc')
                ->get();

            foreach ($conversions as $conversion) {
                $fileName = $conversion->conversion;
                $file = database_path('conversions') . '/' . $fileName . '.php';

                if (File::exists($file)) {
                    $this->line("Rolling back: {$fileName}");

                    try {
                        $instance = include $file;

                        if (!is_object($instance)) {
                            $this->error("The file {$fileName}.php did not return a class instance.");
                            continue;
                        }

                        if (!method_exists($instance, 'down')) {
                            $this->error("Method 'down' does not exist in the conversion returned by {$fileName}.php");
                            continue;
                        }

                        $instance->down();

                        DB::table('conversions')->where('id', $conversion->id)->delete();

                        $this->info("Rolled back: {$fileName}");
                    } catch (\Exception $e) {
                        $this->error("Failed to rollback {$fileName}: {$e->getMessage()}");
                    }
                } else {
                    $this->error("Conversion file {$fileName}.php does not exist.");
                }
            }
        }
    }
}
