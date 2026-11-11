<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskComment;
use App\Services\AuditService;
use App\Services\NumberSequenceService;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $query = Task::with(['client', 'creator', 'assignees']);

        if ($request->get('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->get('department')) {
            $query->where('department', $request->get('department'));
        }
        if ($request->get('priority')) {
            $query->where('priority', $request->get('priority'));
        }
        if ($request->get('q')) {
            $query->where('title', 'like', '%'.$request->get('q').'%')
                ->orWhere('task_number', 'like', '%'.$request->get('q').'%');
        }
        // Team associates only see their own tasks
        if (auth()->user()->hasRole('team_associate')) {
            $query->whereHas('assignments', fn ($q) => $q->where('user_id', auth()->id()));
        }

        $tasks = $query->latest()->paginate(15)->withQueryString();

        return view('erp.tasks.index', compact('tasks'));
    }

    /**
     * Kanban-style task board.
     */
    public function board(Request $request)
    {
        $query = Task::with(['client', 'creator', 'assignees']);

        if ($request->get('department')) {
            $query->where('department', $request->get('department'));
        }
        if (auth()->user()->hasRole('team_associate')) {
            $query->whereHas('assignments', fn ($q) => $q->where('user_id', auth()->id()));
        }

        $tasks = $query->get();
        $columns = config('erp.task_statuses');

        $board = [];
        foreach ($columns as $column) {
            $board[$column] = $tasks->where('status', $column)->values();
        }

        return view('erp.tasks.board', compact('board', 'columns'));
    }

    public function create()
    {
        $clients = \App\Models\Client::where('status', 'active')->orderBy('business_name')->get();
        $assignees = \App\Models\User::where('is_active', true)->orderBy('name')->get();

        return view('erp.tasks.create', compact('clients', 'assignees'));
    }

    /**
     * CSR task distribution pool — centralized queue of unassigned tasks.
     */
    public function pool(Request $request)
    {
        $query = Task::with(['client', 'creator'])
            ->where('status', 'pending')
            ->whereDoesntHave('assignments');

        if ($request->get('department')) {
            $query->where('department', $request->get('department'));
        }
        if ($request->get('priority')) {
            $query->where('priority', $request->get('priority'));
        }
        if ($request->get('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%'.$request->get('q').'%')
                    ->orWhere('task_number', 'like', '%'.$request->get('q').'%');
            });
        }

        $tasks = $query->orderBy('priority')->latest()->paginate(15)->withQueryString();

        $iconTasks = '<svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>';
        $iconAlert = '<svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>';
        $iconTrendUp = '<svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>';
        $iconClock = '<svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';

        // Pool stats
        $stats = [
            'total' => Task::where('status', 'pending')->whereDoesntHave('assignments')->count(),
            'urgent' => Task::where('status', 'pending')->whereDoesntHave('assignments')->where('priority', 'urgent')->count(),
            'high' => Task::where('status', 'pending')->whereDoesntHave('assignments')->where('priority', 'high')->count(),
            'medium' => Task::where('status', 'pending')->whereDoesntHave('assignments')->where('priority', 'medium')->count(),
        ];

        return view('erp.tasks.pool', compact('tasks', 'stats', 'iconTasks', 'iconAlert', 'iconTrendUp', 'iconClock'));
    }

    /**
     * Claim a task from the pool (assign to current user).
     */
    public function claim(Task $task)
    {
        if ($task->status !== 'pending' || $task->assignments()->whereNull('unassigned_at')->exists()) {
            return back()->with('error', 'Task is already assigned.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($task) {
            $task->assignments()->create([
                'user_id' => auth()->id(),
                'assigned_by' => auth()->user()->name,
                'assigned_at' => now(),
            ]);

            $task->update(['status' => 'assigned']);
            $task->statusHistory()->create(['from_status' => 'pending', 'to_status' => 'assigned', 'changed_by' => auth()->id()]);

            AuditService::log('TASK_CLAIM', 'task', $task->id, ['assignee' => auth()->id()]);
        });

        return redirect()->route('erp.tasks.show', $task)->with('success', 'Task claimed and assigned to you.');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => ['nullable', 'exists:clients,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'department' => ['nullable', 'string', 'in:US TAX,UK TAX,LEGAL,GENERAL'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'assignee_id' => ['nullable', 'exists:users,id'],
        ]);

        $data['task_number'] = NumberSequenceService::next('tasks', 'TSK-');
        $data['status'] = ($data['assignee_id'] ?? null) ? 'assigned' : 'pending';
        $data['created_by'] = auth()->id();

        $task = Task::create($data);

        if ($request->filled('assignee_id')) {
            $task->assignments()->create([
                'user_id' => $request->assignee_id,
                'assigned_by' => auth()->user()->name,
                'assigned_at' => now(),
            ]);

            \App\Models\Notification::create([
                'user_id' => $request->assignee_id,
                'type' => 'task_assigned',
                'title' => 'New task assigned: '.$task->title,
                'body' => $task->description,
                'link' => route('erp.tasks.show', $task),
            ]);

            \App\Services\EventNotifier::taskAssigned($task, $request->assignee_id);
        }

        $task->statusHistory()->create(['from_status' => null, 'to_status' => $task->status, 'changed_by' => auth()->id()]);

        AuditService::log('TASK_CREATE', 'task', $task->id, ['title' => $task->title]);

        return redirect()->route('erp.tasks.show', $task)->with('success', 'Task created.');
    }

    public function show(Task $task)
    {
        $this->authorizeTask($task);

        $task->load(['client', 'creator', 'assignees', 'comments.user', 'attachments', 'escalations', 'statusHistory.changedBy']);

        $assignees = \App\Models\User::where('is_active', true)->orderBy('name')->get();

        return view('erp.tasks.show', compact('task', 'assignees'));
    }

    /**
     * Legal task state transitions.
     * cancelled is allowed from any non-terminal state.
     */
    private const ALLOWED_TRANSITIONS = [
        'pending'   => ['assigned', 'cancelled'],
        'assigned'  => ['in_progress', 'cancelled'],
        'in_progress' => ['review', 'cancelled'],
        'review'    => ['completed', 'in_progress', 'cancelled'],
        'completed' => [], // terminal
        'cancelled' => [], // terminal
    ];

    public function updateStatus(Request $request, Task $task)
    {
        $this->authorizeTask($task);

        $data = $request->validate([
            'status' => ['required', 'in:pending,assigned,in_progress,review,completed,cancelled'],
            'reason' => ['nullable', 'string'],
        ]);

        $from = $task->status;
        $to = $data['status'];

        $allowed = self::ALLOWED_TRANSITIONS[$from] ?? [];
        if (! in_array($to, $allowed)) {
            $msg = "Cannot transition from '{$from}' to '{$to}'.";
            if ($request->expectsJson()) {
                return response()->json(['error' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($task, $to, $from, $data) {
            $task->update([
                'status' => $to,
                'completed_at' => $to === 'completed' ? now() : ($to === 'in_progress' ? null : $task->completed_at),
                'completed_by' => $to === 'completed' ? auth()->id() : $task->completed_by,
            ]);

            $task->statusHistory()->create([
                'from_status' => $from,
                'to_status' => $to,
                'changed_by' => auth()->id(),
                'reason' => $data['reason'] ?? null,
            ]);

            AuditService::log('TASK_STATUS_CHANGE', 'task', $task->id, ['from' => $from, 'to' => $to]);
        });

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'status' => $task->status]);
        }

        return back()->with('success', 'Task status updated.');
    }

    public function assign(Request $request, Task $task)
    {
        $this->authorizeTask($task);

        $data = $request->validate([
            'assignee_id' => ['required', 'exists:users,id'],
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($task, $data) {
            $task->assignments()->whereNull('unassigned_at')->update(['unassigned_at' => now()]);
            $task->assignments()->create([
                'user_id' => $data['assignee_id'],
                'assigned_by' => auth()->user()->name,
                'assigned_at' => now(),
            ]);

            $task->update(['status' => 'assigned']);

            \App\Models\Notification::create([
                'user_id' => $data['assignee_id'],
                'type' => 'task_assigned',
                'title' => 'Task assigned: '.$task->title,
                'link' => route('erp.tasks.show', $task),
            ]);

            \App\Services\EventNotifier::taskAssigned($task, $data['assignee_id']);

            AuditService::log('TASK_ASSIGN', 'task', $task->id, ['assignee' => $data['assignee_id']]);
        });

        return back()->with('success', 'Task assigned.');
    }

    public function comment(Request $request, Task $task)
    {
        $this->authorizeTask($task);

        $data = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $task->comments()->create([
            'user_id' => auth()->id(),
            'content' => $data['content'],
        ]);

        return back()->with('success', 'Comment added.');
    }

    public function escalate(Request $request, Task $task)
    {
        $this->authorizeTask($task);

        $level = ($task->escalations()->max('escalation_level') ?? 0) + 1;

        $task->escalations()->create([
            'escalation_level' => $level,
            'reason' => $request->input('reason', 'Manually escalated'),
            'escalated_to' => auth()->id(),
            'escalated_at' => now(),
        ]);

        AuditService::log('TASK_ESCALATE', 'task', $task->id, ['level' => $level]);

        return back()->with('success', 'Task escalated to level '.$level.'.');
    }

    public function destroy(Task $task)
    {
        if (! auth()->user()->hasRole('super_admin') && $task->created_by !== auth()->id()) {
            abort(403, 'Only the task creator or a super admin can delete tasks.');
        }

        if ($task->status === 'completed') {
            return back()->with('error', 'Completed tasks cannot be deleted.');
        }

        $task->delete();

        return redirect()->route('erp.tasks.index')->with('success', 'Task deleted.');
    }

    /**
     * Authorization gate: team_associates may only access tasks assigned to them.
     */
    private function authorizeTask(Task $task): void
    {
        if (auth()->user()->hasRole('team_associate')) {
            $isAssigned = $task->assignments()
                ->where('user_id', auth()->id())
                ->whereNull('unassigned_at')
                ->exists();

            if (! $isAssigned) {
                abort(403, 'You are not assigned to this task.');
            }
        }
    }
}