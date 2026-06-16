<?php

namespace App\Console\Commands;

use App\Models\BankHoliday;
use Illuminate\Console\Command;

class ImportBankHolidays extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:bank-holidays';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command import bank holidays from gov.uk';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = 'https://www.gov.uk/bank-holidays.json';

        $bankHolidays = json_decode(file_get_contents($url), true);

        foreach ($bankHolidays as $key => $value) {
            $this->info($key);
            $events = $bankHolidays[$key]['events'];

            foreach ($events as $event) {
                BankHoliday::firstOrCreate(
                    [
                    'holiday_date' => $event['date']
                    ],
                    [
                        'country' => 'GB',
                        'region' => $key,
                        'title' => $event['title'],
                        'holiday_date' => $event['date']
                    ]
                );

            }
        }

        $this->info('table bank_holidays updated successfully');
        return 0;
    }
}
