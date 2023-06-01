<?php

namespace App\Console;

use App\Console\Commands\DropOffReport;
use App\Console\Commands\MotorRenewalNotice;
use App\Console\Commands\Settlement\EGHLSettlement;
use App\Console\Commands\Settlement\HowdenSettlement;
use App\Console\Commands\Settlement\InsurerSettlement;
use App\Console\Commands\Settlement\MonthlySettlement;
use App\Console\Commands\TrendReport;
use Carbon\Carbon;
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
        /// a. eGHL Settlement
        $schedule->command(EGHLSettlement::class)
            ->dailyAt('08:00');

        /// b. Insurers Settlement
        $schedule->command(InsurerSettlement::class)
            ->dailyAt('08:05');

        /// c. Howden Internal Settlement
        $schedule->command(HowdenSettlement::class)
            ->dailyAt('08:10');

        /// d. Monthly Howden Internal Settlement [First Business Day of Each Month]
        $schedule->command(MonthlySettlement::class)
            ->at('10:10')
            ->when(function () {
                return Carbon::now()->isSameDay($this->firstBusinessDay());
            });

        // 2. Motor Renewal Notice [1 Month, 2 Weeks, 1 Week] (Before Expiry)
        $schedule->command(MotorRenewalNotice::class)->dailyAt('10:00');

        // 3. Trend Report
        $schedule->command(TrendReport::class)->weeklyOn(1, '08:30');

        // 4. Drop Off Report
        $schedule->command(DropOffReport::class)->dailyAt('08:00');
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

    private function firstBusinessDay()
    {
        $first = Carbon::now()->firstOfMonth();

        if($first->isWeekday()) {
            return $first;
        }

        return $first->nextWeekday();
    }
}
