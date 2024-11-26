<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ConversionStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'conversion:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show the status of each conversion file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->ensureConversionsTableExists();

        $this->showConversionStatus();

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
                $table->string('conversion')->unique();
                $table->integer('batch');
                $table->timestamps();
            });
            $this->info('Created conversions table.');
        }
    }

    /**
     * Display the status of each conversion file.
     *
     * @return void
     */
    protected function showConversionStatus()
    {
        // Retrieve all executed conversions with their batch and timestamps
        $executedConversions = DB::table('conversions')->get();

        // Create a map of executed conversions for quick lookup
        $executedConversionsMap = $executedConversions->keyBy('conversion');

        $files = File::glob(database_path('conversions') . '/*.php');

        // Sort files for consistent display
        sort($files);

        $statusData = [];

        foreach ($files as $file) {
            $fileName = basename($file, '.php');

            if ($executedConversionsMap->has($fileName)) {
                $status = '<info>Yes</info>';
                $batchNumber = $executedConversionsMap->get($fileName)->batch;
                $executedAt = $executedConversionsMap->get($fileName)->created_at;
            } else {
                $status = '<fg=red>No</fg=red>';
                $batchNumber = 'N/A';
                $executedAt = 'N/A';
            }

            $statusData[] = [
                'Ran?' => $status,
                'Conversion' => $fileName,
                'Batch' => $batchNumber,
                'Executed At' => $executedAt,
            ];
        }

        if (empty($statusData)) {
            $this->info('No conversion files found.');
            return;
        }

        // Display the table with the additional columns
        $this->table(['Ran?', 'Conversion', 'Batch', 'Executed At'], $statusData);
    }
}
