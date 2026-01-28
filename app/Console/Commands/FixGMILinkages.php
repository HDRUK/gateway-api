<?php

namespace App\Console\Commands;

use App\Models\DatasetVersion;
use Illuminate\Console\Command;
use App\Models\DurHasDatasetVersion;

class FixGMILinkages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-gmi-linkages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command updates broken dur-dataset linkages that were lost during GMI setup period. This is not-destructive, leaving the old linkages still in place.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // IDs specific to production environment - this is not meant to be run in dev!
        $dvMappings = [
            1705 => 1792,
            533 => 1807,
            1343 => 1796,
            1409 => 1794,
            1183 => 1797,
            1501 => 1795,
            520 => 1800,
            534 => 1808,
            535 => 1809,
            966 => 1813,
            540 => 1813,
            1627 => 1793,
            529 => 1803,
            522 => 1820,
            538 => 1812,
            957 => 1812,
            526 => 1798,
            518 => 1802,
            527 => 1816,
            537 => 1811,
            958 => 1814,
            960 => 1814,
            959 => 1814,
            541 => 1814,
            961 => 1814,
            528 => 1815,
            532 => 1806,
            519 => 1801,
            521 => 1799,
            525 => 1817,
            536 => 1810,
            531 => 1805,
            1672 => 1791,
            524 => 1818,
            523 => 1819,
            530 => 1804
        ];

        foreach ($dvMappings as $previousId => $newId) {
            $this->info('Replicating dur links with previous dataset version ' . $previousId . ' by now linking to new dataset version ' . $newId);

            $previousDV = DatasetVersion::find($previousId);
            if (!$previousDV) {
                $this->warn('Skipping ' . $previousId . ' => ' . $newId . ' as previous DV not found');
                continue;
            }
            $this->info('Found previous DV ' . $previousId);

            $newDV = DatasetVersion::find($newId);
            if (!$newDV) {
                $this->warn('Skipping ' . $previousId . ' => ' . $newId . ' as new DV not found');
                continue;
            }
            $this->info('Found new DV ' . $newId);

            // Get all existing linkages
            $existingDursLinked = DurHasDatasetVersion::where(['dataset_version_id' => $previousId])
                ->whereNull('deleted_at')
                ->select(['dur_id', 'user_id'])
                ->get();
            if (count($existingDursLinked) == 0) {
                $this->warn('No linked DURs found');
            }
            $this->info('Found linked DURs ' . implode(', ', $existingDursLinked->pluck('dur_id')->toArray()));

            // createOrUpdate same linkage but to new DV
            foreach ($existingDursLinked as $linkage) {
                $this->info('Adding new DurHasDatasetVersion ' .  print_r([
                    'dur_id' => $linkage->dur_id,
                    'dataset_version_id' => $newId,
                    'user_id' => $linkage->user_id,
                ], true));
                DurHasDatasetVersion::withoutEvents(function () use ($linkage, $newId) {
                    DurHasDatasetVersion::updateOrCreate([
                        'dur_id' => $linkage->dur_id,
                        'dataset_version_id' => $newId,
                        'user_id' => $linkage->user_id,
                    ]);
                });
            }
        }

        $this->info('Command completed successfully.');
        return Command::SUCCESS;
    }
}
