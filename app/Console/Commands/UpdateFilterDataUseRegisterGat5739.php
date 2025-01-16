<?php

namespace App\Console\Commands;

use App\Models\Filter;
use Illuminate\Console\Command;

class UpdateFilterDataUseRegisterGat5739 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-filter-data-use-register-gat5739';

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
        Filter::updateOrCreate([
            'type' => 'dataUseRegister',
            'keys' => 'collectionNames',
        ]);
    }
}
