<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class EmailScanningService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:email-scanning-service';

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
        //
        dd("yo");
    }
}
