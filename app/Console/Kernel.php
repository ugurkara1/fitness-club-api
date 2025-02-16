<?php

namespace App\Console;

use App\Console\Commands\SendAppointmentReminder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
//zamanlanmış görevleri planlar
class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        SendAppointmentReminder::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Her saat başı randevu hatırlatma komutunu çalıştır
        $schedule->command('appointment:reminder')->hourly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        // Bu dosyadaki komutların yolu
        $this->load(__DIR__.'/Commands');

        // Varsayılan Artisan komutlarını kaydet
        require base_path('routes/console.php');
    }
}