<?php

namespace App\Console;

use App\Console\Commands\ComplianceExpiryScan;
use App\Console\Commands\EmailQueueWorker;
use App\Console\Commands\MigrateLegacyData;
use App\Console\Commands\MigrateLegacyPdfs;
use App\Console\Commands\RecalculateKpis;
use App\Console\Commands\TaskDeadlineCheck;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     */
    protected $commands = [
        ComplianceExpiryScan::class,
        TaskDeadlineCheck::class,
        EmailQueueWorker::class,
        RecalculateKpis::class,
        MigrateLegacyData::class,
        MigrateLegacyPdfs::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Compliance: daily scan at 02:00
        $schedule->command('erp:compliance-scan')->dailyAt('02:00');

        // Task deadlines: every 15 minutes during business hours
        $schedule->command('erp:task-deadlines')->everyFifteenMinutes();

        // Email queue: every 5 minutes
        $schedule->command('erp:email-process')->everyFiveMinutes();

        // KPI: daily at 03:00
        $schedule->command('erp:kpi-recalc')->dailyAt('03:00');
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