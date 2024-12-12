<?php

namespace App\Console\Commands;

use App\Models\Team;
use Illuminate\Console\Command;

class AddDarModalDetails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-dar-modal-details';

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
        $items = [
            'Neonatal Data Analysis Unit - Imperial College London',
            'MIREDA',
            'SAIL',
            'Public Health Scotland',
            'Health And Social Care Northern Ireland',
            'INSIGHT',
            'ISARIC 4C',
            'Great Ormond Street Hospital',
            'Office For National Statistics',
        ];

        $darModelHeader = 'Data access requests not enabled for this Data Custodian';
        $darModelContent = 'This Data Custodian uses the Gateway Data Access Request Module. The Module is temporarily unavailable while we make improvements. In the interim, please submit a general enquiry to the Data Custodian directly, who will provide more details on how to submit a Data Access Request form. For future applications, please return to the Gateway to use the Data Access Request Module.';
        $darModelFooter = '';

        foreach ($items as $item) {
            $team = Team::where('name', 'like', '%' . $item . '%')->first();

            if (!is_null($team)) {
                $this->info('Updating: ' . $team->name);
                $team->update([
                    'dar_modal_header' => $darModelHeader,
                    'dar_modal_content' => $darModelContent,
                    'dar_modal_footer' => $darModelFooter,
                    'is_dar' => 1,
                ]);
                $this->info($team->name . ' updated successfully');
            } else {
                $this->warn($item . ' not found for update');
            }
        }
    }
}
