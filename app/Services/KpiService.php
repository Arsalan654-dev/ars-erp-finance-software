<?php

namespace App\Services;

use App\Models\KpiDefinition;
use App\Models\EmployeeKpiResult;
use App\Models\Task;

/**
 * Employee KPI calculation engine.
 */
class KpiService
{
    /**
     * Recalculate auto KPIs for all users for a given period (YYYY-MM).
     */
    public static function recalculateAll(?string $period = null): int
    {
        $period = $period ?? now()->format('Y-m');
        $count = 0;

        $definitions = KpiDefinition::where('calculation_method', 'auto')->where('is_active', true)->get();

        foreach ($definitions as $definition) {
            foreach (\App\Models\User::where('is_active', true)->get() as $user) {
                $result = self::calculateForUser($definition, $user->id, $period);
                if ($result) {
                    $count++;
                }
            }
        }

        return $count;
    }

    public static function calculateForUser(KpiDefinition $definition, int $userId, string $period): ?EmployeeKpiResult
    {
        $actual = self::computeValue($definition, $userId, $period);
        if ($actual === null) {
            return null;
        }

        $target = $definition->targets()
            ->where('user_id', $userId)
            ->where('period', $period)
            ->value('target_value');

        $target = $target ?? $definition->targets()
            ->whereNull('user_id')
            ->where('period', $period)
            ->value('target_value');

        $score = null;
        if ($target && $target > 0) {
            $direction = $definition->direction ?? 'higher_better';
            if ($direction === 'lower_better') {
                // For metrics where lower is better (e.g. avg_completion_days, tasks_overdue):
                // actual <= target → 100; actual > target → score degrades
                $score = $actual <= $target ? 100 : min(100, round(($target / $actual) * 100, 2));
            } else {
                // Default: higher is better (e.g. tasks_completed, completion_rate)
                $score = min(100, round(($actual / $target) * 100, 2));
            }
        }

        return EmployeeKpiResult::updateOrCreate(
            [
                'kpi_definition_id' => $definition->id,
                'user_id' => $userId,
                'period' => $period,
            ],
            [
                'actual_value' => $actual,
                'target_value' => $target,
                'score' => $score,
            ]
        );
    }

    private static function computeValue(KpiDefinition $definition, int $userId, string $period): ?float
    {
        $range = self::periodRange($period);

        switch ($definition->slug) {
            case 'tasks_assigned':
                return (float) Task::whereHas('assignments', fn ($q) => $q->where('user_id', $userId))
                    ->whereBetween('created_at', $range)
                    ->count();

            case 'tasks_completed':
                return (float) Task::where('completed_by', $userId)
                    ->whereBetween('completed_at', $range)
                    ->count();

            case 'tasks_overdue':
                return (float) Task::whereHas('assignments', fn ($q) => $q->where('user_id', $userId))
                    ->whereNotNull('due_date')
                    ->where('due_date', '<', now())
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->whereBetween('created_at', $range)
                    ->count();

            case 'completion_rate':
                $assigned = Task::whereHas('assignments', fn ($q) => $q->where('user_id', $userId))
                    ->whereBetween('created_at', $range)
                    ->count();
                $completed = Task::where('completed_by', $userId)->whereBetween('completed_at', $range)->count();

                return $assigned > 0 ? round(($completed / $assigned) * 100, 2) : 0.0;

            case 'avg_completion_days':
                return self::avgCompletionDays($userId, $range);

            case 'client_count':
                return (float) \App\Models\Client::where('created_by', $userId)->whereBetween('created_at', $range)->count();

            default:
                return null;
        }
    }

    private static function avgCompletionDays(int $userId, array $range): ?float
    {
        $tasks = Task::where('completed_by', $userId)
            ->whereBetween('completed_at', $range)
            ->whereNotNull('created_at')
            ->get();

        if ($tasks->isEmpty()) {
            return 0.0;
        }

        $total = $tasks->sum(fn ($t) => max(0, $t->completed_at->diffInDays($t->created_at)));

        return round($total / $tasks->count(), 2);
    }

    private static function periodRange(string $period): array
    {
        return [
            \Carbon\Carbon::parse($period.'-01')->startOfMonth(),
            \Carbon\Carbon::parse($period.'-01')->endOfMonth(),
        ];
    }
}