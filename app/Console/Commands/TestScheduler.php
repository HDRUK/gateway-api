<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Log;
use Auditor;
use CloudLogger;

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
        Log::info('using log test scheduler :: ' . now()->toDateTimeString());

        Auditor::log([
            'action_type' => 'EXCEPTION',
            'action_name' => class_basename($this) . '@' . __FUNCTION__,
            'description' => 'using auditor test scheduler :: ' . now()->toDateTimeString(),
        ]);

        CloudLogger::write('test scheduler - cloudlogger :: ' . now()->toDateTimeString());
    }
}
