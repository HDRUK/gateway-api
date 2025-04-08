<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateDurOrgSectorGat6679 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-dur-org-sector-gat6679';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        \DB::statement('UPDATE dur SET organisation_sector = NULL WHERE sector_id IS NULL');

        $this->info('Done');
    }
}
