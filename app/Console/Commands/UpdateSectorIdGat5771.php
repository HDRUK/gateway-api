<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UpdateSectorIdGat5771 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-sector-id-gat5771';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sector ID is Null in database for newly created accounts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        User::whereNull('sector_id')->update(['sector_id' => 6]);

        $this->info('done');
    }
}
