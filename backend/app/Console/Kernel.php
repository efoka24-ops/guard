<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Pas de worker long-running garanti sur le mutualisé Camoo : le cron
        // cPanel lance schedule:run chaque minute, qui traite un lot borné de jobs.
        $schedule->command('queue:work --stop-when-empty --max-time=50')
            ->everyMinute()
            ->withoutOverlapping();

        // GUARD WEB (module #2) — cf. guard-web-plan.md §4.
        $schedule->command('web:verifier defacements')->everyTwoMinutes()->withoutOverlapping();
        $schedule->command('web:verifier en-tetes')->weekly()->withoutOverlapping();
        $schedule->command('web:verifier ssl')->daily()->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
