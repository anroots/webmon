<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('scan:schedule')
            ->everyFiveMinutes();

        $schedule->command('health:check')
            ->everyFifteenMinutes();

        $schedule->command('horizon:snapshot')
            ->everyFiveMinutes();

        $schedule->command('domain:import-ct --tld=ee https://certstream-sniffer.sqroot.eu/get-domains')
            ->everyFiveMinutes();

        $schedule->command('domain:download-list')
            ->twiceDaily();

        $schedule->command('domain:import-csv', ['--tld=ee', '--tags=afxr', storage_path('added.txt')])
            ->twiceDaily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
