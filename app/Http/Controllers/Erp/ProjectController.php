<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\ProjectMember;
use App\Models\ProjectStatusHistory;
use App\Services\AuditService;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::with(['client', 'invoice', 'assignedBy', 'members.user']);

        if ($request->get('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->get('department')) {
            $query->where('department', $request->get('department'));
        }
        if ($request->get('client_id')) {
            $query->where('client_id', $request->get('client_id'));
        }
        if ($request->get('q')) {
            $q = $request->get('q');
            $query->where(function ($query) use ($q) {
                $query->where('title', 'like', "%{$q}%")
                    ->orWhere('project_number', 'like', "%{$q}%");
            });
        }

        $projects = $query->latest()->paginate(15)->withQueryString();

        $counts = [
            'total' => Project::count(),
            'pending' => Project::where('status', 'pending')->count(),
            'in_progress' => Project::where('status', 'in_progress')->count(),
            'review' => Project::where('status', 'review')->count(),
            'completed' => Project::where('status', 'completed')->count(),
            'on_hold' => Project::where('status', 'on_hold')->count(),
        ];

        return view('erp.projects.index', compact('projects', 'counts'));
    }

    public function create()
    {
        $clients = Client::orderBy('business_name')->get();
        $invoices = Invoice::whereIn('status', ['draft', 'locked', 'sent'])->orderBy('invoice_number')->get();
        $users = \App\Models\User::where('is_active', true)->orderBy('name')->get();

        return view('erp.projects.create', compact('clients', 'invoices', 'users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'invoice_id' => ['nullable', 'exists:invoices,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'department' => ['required', 'in:'.implode(',', config('erp.departments'))],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'members' => ['nullable', 'array'],
            'members.*' => ['exists:users,id'],
        ]);

        $data['project_number'] = 'PRJ-' . strtoupper(uniqid());
        $data['status'] = 'pending';
        $data['created_by'] = auth()->id();
        $data['assigned_by'] = auth()->id();

        $members = $data['members'] ?? [];
        unset($data['members']);

        $project = Project::create($data);

        foreach ($members as $userId) {
            $project->members()->create([
                'user_id' => $userId,
                'role_in_project' => 'member',
                'joined_at' => now(),
            ]);
        }

        $project->statusHistory()->create([
            'from_status' => null, 'to_status' => 'pending', 'changed_by' => auth()->id(), 'created_at' => now(),
        ]);

        AuditService::log('PROJECT_CREATE', 'project', $project->id, ['title' => $data['title']]);

        return redirect()->route('erp.projects.show', $project)->with('success', 'Project created.');
    }

    public function show(Project $project)
    {
        $project->load(['client', 'invoice', 'assignedBy', 'creator', 'members.user', 'tasks.assignees', 'statusHistory.changedBy']);

        return view('erp.projects.show', compact('project'));
    }

    public function edit(Project $project)
    {
        $clients = Client::orderBy('business_name')->get();
        $invoices = Invoice::whereIn('status', ['draft', 'locked', 'sent'])->orderBy('invoice_number')->get();
        $users = \App\Models\User::where('is_active', true)->orderBy('name')->get();

        return view('erp.projects.edit', compact('project', 'clients', 'invoices', 'users'));
    }

    public function update(Request $request, Project $project)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'department' => ['required', 'in:'.implode(',', config('erp.departments'))],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
        ]);

        $project->update($data);

        return redirect()->route('erp.projects.show', $project)->with('success', 'Project updated.');
    }

    public function transition(Request $request, Project $project)
    {
        $data = $request->validate([
            'status' => ['required', 'in:in_progress,review,completed,on_hold,pending,cancelled'],
            'reason' => ['nullable', 'string'],
        ]);

        if (! $project->canTransitionTo($data['status'])) {
            return back()->with('error', "Cannot transition from '{$project->status}' to '{$data['status']}'.");
        }

        $fromStatus = $project->status;
        $project->update([
            'status' => $data['status'],
            'completed_at' => $data['status'] === 'completed' ? now() : $project->completed_at,
        ]);

        $project->statusHistory()->create([
            'from_status' => $fromStatus,
            'to_status' => $data['status'],
            'changed_by' => auth()->id(),
            'reason' => $data['reason'] ?? null,
            'created_at' => now(),
        ]);

        AuditService::log('PROJECT_STATUS_CHANGE', 'project', $project->id, ['from' => $fromStatus, 'to' => $data['status']]);

        return back()->with('success', "Project status updated to '{$data['status']}'.");
    }

    public function addMember(Request $request, Project $project)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'role_in_project' => ['required', 'in:lead,member,viewer'],
        ]);

        $project->members()->updateOrCreate(
            ['user_id' => $data['user_id']],
            ['role_in_project' => $data['role_in_project'], 'joined_at' => now()]
        );

        return back()->with('success', 'Member added.');
    }

    public function removeMember(Project $project, ProjectMember $member)
    {
        $member->delete();
        return back()->with('success', 'Member removed.');
    }
}
