<?php

namespace App\Console\Commands;

use App\Services\KpiService;
use Illuminate\Console\Command;

/**
 * Daily KPI recalculation.
 */
class RecalculateKpis extends Command
{
    protected $signature = 'erp:kpi-recalc {--period= : Period in YYYY-MM format}';

    protected $description = 'Recalculate auto KPIs for all employees';

    public function handle(): int
    {
        $period = $this->option('period') ?: now()->format('Y-m');
        $count = KpiService::recalculateAll($period);

        $this->info("KPI recalculation complete: {$count} results updated for {$period}.");

        return self::SUCCESS;
    }
}