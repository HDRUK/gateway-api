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

        foreach ($items as $item) {
            $team = Team::where('name', 'like', '%' . $item . '%')->first();

            if (!is_null($team)) {
                Team::where('name', $item)->update([
                    'dar_modal_header' => $darModelHeader,
                    'dar_modal_content' => $darModelContent,
                ]);
                $this->info($item . ' updated');
            } else {
                $this->warning($item . ' not found for update');
            }
        }
    }
}
