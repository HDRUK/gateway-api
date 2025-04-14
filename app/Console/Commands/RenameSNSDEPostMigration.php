<?php

namespace App\Console\Commands;

use App\Models\DataProviderColl;

use Illuminate\Console\Command;

class RenameSNSDEPostMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:rename-s-n-s-d-e-post-migration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Renames the SNSDE collection to the actual name';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dp = DataProviderColl::where('name', '=', 'SNSDE')->first();
        if ($dp) {
            $dp->update([
                'name' => 'NHS Research Secure Data Environment (SDE) Network',
            ]);
        }
    }
}
