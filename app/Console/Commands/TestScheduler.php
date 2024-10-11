<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Log;

class TestScheduler extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-scheduler';

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
        // send log
        Log::info('using log :: ' . now()->toDateTimeString());
    }
}
