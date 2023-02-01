<?php

namespace App\Console;

use App\Console\Commands\EGHLSettlement;
use App\Console\Commands\HowdenSettlement;
use App\Console\Commands\InsurerSettlement;
use App\Console\Commands\MotorRenewalNotice;
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
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // 1. Settlement Reports [Wed, Fri]
        $schedule->command(EGHLSettlement::class)
            ->dailyAt('08:00')
            ->days([Schedule::WEDNESDAY, Schedule::FRIDAY]);
        $schedule->command(InsurerSettlement::class)
            ->dailyAt('08:05')
            ->days([Schedule::WEDNESDAY, Schedule::FRIDAY]);
        $schedule->command(HowdenSettlement::class)
            ->dailyAt('08:10')
            ->days([Schedule::WEDNESDAY, Schedule::FRIDAY]);
            
        // 2. Motor Renewal Notice [1 Month, 2 Weeks, 1 Week] (Before Expiry)
        $schedule->command(MotorRenewalNotice::class)->dailyAt('10:00');

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
