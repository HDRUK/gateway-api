<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReindexAllTables extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reindex';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reindex all tables in the MySQL database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $database = config('database.connections.mysql.database');

        $tables = DB::select('SHOW TABLES');

        $bar = $this->output->createProgressBar(count($tables));
        $bar->start();

        foreach ($tables as $table) {
            $tableName = $table->{'Tables_in_' . $database};
            DB::statement("OPTIMIZE TABLE {$tableName}");
            $bar->advance();
        }

        $bar->finish();

        $this->info(PHP_EOL . 'Reindexing all tables.');
    }
}
