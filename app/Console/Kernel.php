<?php

namespace App\Console;

use App\Jobs\AliasReplyScannerJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // // Runs this command daily at midnight
        // $schedule->command('app:cohort-user-expiry')->dailyAt('02:00');

        // // runs the ARS email scanner
        // // $schedule->command('app:alias-reply-scanner')->everyFiveMinutes();
        // $schedule->job(new AliasReplyScannerJob())->everyFiveMinutes();

        // // update license information from EU server
        // $schedule->command('app:update-licenses')->monthlyOn(1, '01:00');

        // // update hubspot contacts information
        // $schedule->command('app:sync-hubspot-contacts')->dailyAt('04:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
