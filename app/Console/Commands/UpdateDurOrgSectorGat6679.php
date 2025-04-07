<?php

namespace App\Console\Commands;

use App\Models\Dur;
use App\Models\Sector;
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
        $durs = Dur::all();

        foreach ($durs as $dur) {
            if ($dur->organisation_sector && strlen($dur->organisation_sector) === 1) {
                $dbOrganizationSector = $dur->organisation_sector;
                $sector = Sector::where('id', (int)$dbOrganizationSector)->first();
                if (!is_null($sector)) {
                    Dur::where([
                        'id' => $dur->id,
                    ])->update([
                        'organisation_sector' => $sector->name
                    ]);
                    $this->info($dur->id . ' updated organisation_sector from ' . $dbOrganizationSector . ' to ' . $sector->name);
                }
            }
        }

        $this->info('Done');
    }
}
