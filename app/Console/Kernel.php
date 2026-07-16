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
        // $schedule->command('inspire')->hourly();

        $schedule->command('app:check-order')->everyMinute();

        // 合成脚本常驻约 2 小时；每分钟尝试拉起，同 flag 已运行则内部 Redis 互斥直接退出
        $schedule->command('app:combine-photo 1')->everyMinute();
        $schedule->command('app:combine-photo 2')->everyMinute();
        $schedule->command('app:combine-photo 3')->everyMinute();
        $schedule->command('app:combine-photo 4')->everyMinute();
        $schedule->command('app:combine-photo 5')->everyMinute();
        $schedule->command('app:combine-photo 6')->everyMinute();
        $schedule->command('app:combine-photo 7')->everyMinute();
        $schedule->command('app:combine-photo 8')->everyMinute();
        $schedule->command('app:combine-photo 9')->everyMinute();
        $schedule->command('app:combine-photo 10')->everyMinute();
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
