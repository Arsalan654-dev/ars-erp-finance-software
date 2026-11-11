<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Task;
use App\Models\TaskEscalation;
use Illuminate\Console\Command;

/**
 * Task deadline + escalation worker:
 *  - warns assignees 24h before due date
 *  - escalates overdue tasks per config schedule (1/3/5 days -> level 1/2/3)
 */
class TaskDeadlineCheck extends Command
{
    protected $signature = 'erp:task-deadlines {--dry-run : Show what would happen}';

    protected $description = 'Check task deadlines, send warnings and escalate overdue tasks';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $escalationSchedule = config('erp.task_escalation', [1 => 1, 3 => 2, 5 => 3]);

        $openTasks = Task::whereNotIn('status', ['completed', 'cancelled'])
            ->whereNotNull('due_date')
            ->with('assignments')
            ->get();

        $warnings = 0;
        $escalations = 0;

        foreach ($openTasks as $task) {
            $daysOverdue = now()->startOfDay()->diffInDays($task->due_date, false);

            // Warning: due within 24h or already overdue by <1 day
            if ($daysOverdue >= -1 && $daysOverdue < 1) {
                // Check per-assignee to avoid skipping other assignees' warnings
                foreach ($task->assignments as $assignment) {
                    $alreadyWarned = Notification::where('type', 'task_deadline_warning')
                        ->where('user_id', $assignment->user_id)
                        ->where('link', route('erp.tasks.show', $task))
                        ->where('created_at', '>=', now()->subHours(24))
                        ->exists();

                    if (! $alreadyWarned) {
                        if (! $dryRun) {
                            Notification::create([
                                'user_id' => $assignment->user_id,
                                'type' => 'task_deadline_warning',
                                'title' => 'Deadline approaching: '.$task->title,
                                'body' => "Task {$task->task_number} is due on {$task->due_date->format('Y-m-d')}.",
                                'link' => route('erp.tasks.show', $task),
                            ]);
                        }
                        $warnings++;
                    }
                }
            }

            // Escalation for overdue tasks
            if ($daysOverdue >= 1) {
                $targetLevel = 0;
                foreach ($escalationSchedule as $days => $level) {
                    if ($daysOverdue >= $days) {
                        $targetLevel = max($targetLevel, $level);
                    }
                }

                $currentMaxLevel = $task->escalations()->max('escalation_level') ?? 0;

                if ($targetLevel > $currentMaxLevel) {
                    if (! $dryRun) {
                        TaskEscalation::create([
                            'task_id' => $task->id,
                            'escalation_level' => $targetLevel,
                            'reason' => "Auto-escalated: {$daysOverdue} days overdue",
                            'escalated_at' => now(),
                        ]);

                        Notification::create([
                            'user_id' => $task->created_by,
                            'type' => 'task_overdue',
                            'title' => "Task escalated (level {$targetLevel}): ".$task->title,
                            'body' => "Task {$task->task_number} is {$daysOverdue} day(s) overdue.",
                            'link' => route('erp.tasks.show', $task),
                        ]);
                    }
                    $escalations++;
                }
            }
        }

        $this->info("Task deadline check complete: {$warnings} warnings, {$escalations} escalations.".($dryRun ? ' (dry run)' : ''));

        return self::SUCCESS;
    }
}