<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Client;
use App\Models\ComplianceRecord;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Task;
use App\Models\User;
use App\Models\Lead;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $role = $user->roles->pluck('name')->first();

        $data = match ($role) {
            'hr' => $this->hrData(),
            'finance' => $this->financeData(),
            'csr_manager' => $this->csrData(),
            'team_associate' => $this->associateData(),
            default => $this->superAdminData(),
        };

        if (! in_array($role, ['hr', 'team_associate'], true)) {
            $data['pipelineCounts'] = $this->pipelineCounts();
        }

        $data['unreadNotifications'] = $user->notifications()->where('is_read', false)->latest()->limit(10)->get();
        $data['role'] = $role;
        $data['pageTitle'] = match ($role) {
            'hr' => 'HR Manager Dashboard',
            'finance' => 'Finance Dashboard',
            'csr_manager' => 'CSR Manager Dashboard',
            'team_associate' => 'My Dashboard',
            default => 'Super Admin Dashboard',
        };
        $data['pageSubtitle'] = match ($role) {
            'hr' => 'People, attendance, training and payroll at a glance.',
            'finance' => 'Revenue, receivables and cash position in real time.',
            'csr_manager' => 'Clients, tasks and deadlines across your teams.',
            'team_associate' => 'Your assigned tasks, deadlines and KPI score.',
            default => 'Complete enterprise overview across every department.',
        };

        return view('erp.dashboard.index', $data);
    }

    private function superAdminData(): array
    {        return [
            'view' => 'super_admin',
            'totalClients' => Client::count(),
            'activeClients' => Client::where('status', 'active')->count(),
            'openTasks' => Task::whereNotIn('status', ['completed', 'cancelled'])->count(),
            'overdueTasks' => Task::whereNotIn('status', ['completed', 'cancelled'])->whereNotNull('due_date')->where('due_date', '<', now())->count(),
            'pendingPayments' => Payment::where('status', 'pending')->count(),
            'outstandingInvoices' => Invoice::whereNotIn('status', ['paid', 'cancelled'])->where('balance', '>', 0)->count(),
            'upcomingRenewals' => ComplianceRecord::whereNotNull('expiry_date')->whereBetween('expiry_date', [now(), now()->addDays(60)])->count(),
            'employeesCount' => User::where('is_active', true)->count(),
            'totalLeads' => Lead::count(),
            'newLeads' => Lead::where('status', 'new')->count(),
            'totalProjects' => Project::count(),
            'pendingProjects' => Project::where('status', 'pending')->count(),
            'recentAudits' => \App\Models\AuditLog::latest()->limit(8)->get(),
            'recentClients' => Client::latest()->limit(6)->get(),
            'recentTasks' => Task::with(['client', 'creator'])->latest()->limit(6)->get(),
            'recentInvoices' => Invoice::with(['client'])->latest()->limit(6)->get(),
            'revenueTrend' => $this->revenueTrend(6),
            'clientStatusBreakdown' => $this->clientStatusBreakdown(),
            'taskStatusBreakdown' => $this->taskStatusBreakdown(),
        ];
    }

    private function hrData(): array
    {
        return [
            'view' => 'hr',
            'totalEmployees' => Employee::count(),
            'activeEmployees' => Employee::where('status', 'active')->count(),
            'presentToday' => AttendanceRecord::whereDate('date', now())->where('status', 'present')->count(),
            'absentToday' => AttendanceRecord::whereDate('date', now())->where('status', 'absent')->count(),
            'onLeaveToday' => AttendanceRecord::whereDate('date', now())->where('status', 'leave')->count(),
            'pendingTraining' => \App\Models\TrainingAssignment::whereNotIn('status', ['completed', 'failed'])->count(),
            'upcomingJoiners' => Employee::whereNotNull('joining_date')->where('joining_date', '>=', now())->count(),
            'payrollPending' => \App\Models\PayrollRecord::where('status', 'pending')->count(),
            'recentEmployees' => Employee::with('user')->latest()->limit(6)->get(),
            'attendanceTrend' => $this->attendanceTrend(7),
            'departmentBreakdown' => $this->departmentBreakdown(),
            'leavePending' => \App\Models\LeaveRequest::where('status', 'pending')->count(),
        ];
    }

    private function financeData(): array
    {
        $month = now()->format('Y-m');
        $range = [now()->startOfMonth(), now()->endOfMonth()];

        return [
            'view' => 'finance',
            'monthlyRevenue' => Invoice::whereBetween('created_at', $range)->sum('total'),
            'outstanding' => Invoice::whereNotIn('status', ['paid', 'cancelled'])->sum('balance'),
            'paidThisMonth' => Payment::where('status', 'verified')->whereBetween('created_at', $range)->sum('pkr_equivalent') ?: Payment::where('status', 'verified')->whereBetween('created_at', $range)->sum('amount'),
            'pendingVerification' => Payment::where('status', 'pending')->count(),
            'pendingApprovals' => Invoice::where('status', 'locked')->whereDoesntHave('latestApproval', function ($q) {
                $q->whereIn('status', ['approved', 'rejected']);
            })->count(),
            'expensesThisMonth' => Expense::where('status', 'approved')->whereBetween('created_at', $range)->sum('amount'),
            'receivables' => Invoice::whereNotIn('status', ['paid', 'cancelled'])->count(),
            'netPosition' => (Invoice::whereBetween('created_at', $range)->sum('total')) - (Expense::where('status', 'approved')->whereBetween('created_at', $range)->sum('amount')),
            'currencyBreakdown' => Payment::where('status', 'verified')->whereBetween('created_at', $range)
                ->selectRaw('currency, SUM(amount) as total')
                ->groupBy('currency')->get(),
            'recentPayments' => Payment::with('client')->latest()->limit(6)->get(),
            'recentInvoices' => Invoice::with('client')->latest()->limit(6)->get(),
            'revenueTrend' => $this->revenueTrend(6),
            'expenseTrend' => $this->expenseTrend(6),
            'paymentStatusBreakdown' => $this->paymentStatusBreakdown(),
        ];
    }

    private function csrData(): array
    {
        return [
            'view' => 'csr_manager',
            'totalClients' => Client::count(),
            'newClients' => Client::where('created_at', '>=', now()->startOfMonth())->count(),
            'openTasks' => Task::whereNotIn('status', ['completed', 'cancelled'])->count(),
            'overdueTasks' => Task::whereNotIn('status', ['completed', 'cancelled'])->whereNotNull('due_date')->where('due_date', '<', now())->count(),
            'completedTasks' => Task::where('status', 'completed')->whereMonth('completed_at', now()->month)->count(),
            'usTaxTasks' => Task::where('department', 'US TAX')->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'ukTaxTasks' => Task::where('department', 'UK TAX')->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'legalTasks' => Task::where('department', 'LEGAL')->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'totalLeads' => Lead::count(),
            'newLeads' => Lead::where('status', 'new')->count(),
            'qualifiedLeads' => Lead::where('status', 'qualified')->count(),
            'totalProjects' => Project::count(),
            'activeProjects' => Project::whereIn('status', ['pending', 'in_progress', 'review'])->count(),
            'upcomingDeadlines' => Task::whereNotIn('status', ['completed', 'cancelled'])->whereNotNull('due_date')->whereBetween('due_date', [now(), now()->addDays(7)])->latest('due_date')->limit(6)->get(),
            'recentTasks' => Task::with(['client', 'assignees'])->latest()->limit(6)->get(),
            'recentClients' => Client::latest()->limit(6)->get(),
            'departmentBreakdown' => $this->taskDepartmentBreakdown(),
            'taskStatusBreakdown' => $this->taskStatusBreakdown(),
            'newClientsTrend' => $this->newClientsTrend(6),
        ];
    }

    private function associateData(): array
    {
        $userId = Auth::id();
        $myTasks = Task::whereHas('assignments', fn ($q) => $q->where('user_id', $userId))->whereNotIn('status', ['completed', 'cancelled']);

        return [
            'view' => 'team_associate',
            'myTasks' => $myTasks->count(),
            'dueToday' => $myTasks->clone()->whereDate('due_date', now())->count(),
            'upcoming' => $myTasks->clone()->whereNotNull('due_date')->where('due_date', '>', now())->count(),
            'overdue' => $myTasks->clone()->whereNotNull('due_date')->where('due_date', '<', now())->count(),
            'completed' => Task::whereHas('assignments', fn ($q) => $q->where('user_id', $userId))->where('status', 'completed')->whereMonth('completed_at', now()->month)->count(),
            'assignedTasks' => $myTasks->clone()->with(['client', 'creator'])->latest()->limit(10)->get(),
            'myKpis' => \App\Models\EmployeeKpiResult::with('kpiDefinition')
                ->where('user_id', $userId)->where('period', now()->format('Y-m'))->get(),
            'myCompletedTrend' => $this->myCompletedTrend($userId, 6),
            'myTaskStatusBreakdown' => $this->myTaskStatusBreakdown($userId),
        ];
    }

    /* ===== Chart data builders ===== */

    private function revenueTrend(int $months): array
    {
        $labels = [];
        $values = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $start = now()->startOfMonth()->subMonths($i);
            $end = now()->startOfMonth()->subMonths($i)->endOfMonth();
            $labels[] = $start->format('M');
            $values[] = (int) Invoice::whereBetween('created_at', [$start, $end])->sum('total');
        }
        return ['labels' => $labels, 'values' => $values];
    }

    private function expenseTrend(int $months): array
    {
        $labels = [];
        $values = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $start = now()->startOfMonth()->subMonths($i);
            $end = now()->startOfMonth()->subMonths($i)->endOfMonth();
            $labels[] = $start->format('M');
            $values[] = (int) Expense::where('status', 'approved')->whereBetween('created_at', [$start, $end])->sum('amount');
        }
        return ['labels' => $labels, 'values' => $values];
    }

    private function attendanceTrend(int $days): array
    {
        $labels = [];
        $present = [];
        $absent = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format('D');
            $present[] = AttendanceRecord::whereDate('date', $date)->where('status', 'present')->count();
            $absent[] = AttendanceRecord::whereDate('date', $date)->whereIn('status', ['absent', 'leave'])->count();
        }
        return ['labels' => $labels, 'present' => $present, 'absent' => $absent];
    }

    private function departmentBreakdown(): array
    {
        return User::where('is_active', true)
            ->selectRaw('COALESCE(NULLIF(department, ""), "N/A") as name, COUNT(*) as total')
            ->groupBy('department')->orderByDesc('total')->limit(8)->get()
            ->map(fn ($r) => ['label' => $r->name, 'value' => (int) $r->total])->values()->toArray();
    }

    private function taskDepartmentBreakdown(): array
    {
        return Task::selectRaw('COALESCE(NULLIF(department, ""), "N/A") as name, COUNT(*) as total')
            ->groupBy('department')->orderByDesc('total')->limit(8)->get()
            ->map(fn ($r) => ['label' => $r->name, 'value' => (int) $r->total])->values()->toArray();
    }

    private function taskStatusBreakdown(): array
    {
        return Task::selectRaw('status as name, COUNT(*) as total')
            ->groupBy('status')->get()
            ->map(fn ($r) => ['label' => ucwords(str_replace('_', ' ', $r->name)), 'value' => (int) $r->total])->values()->toArray();
    }

    private function clientStatusBreakdown(): array
    {
        return Client::selectRaw('COALESCE(status, "unknown") as name, COUNT(*) as total')
            ->groupBy('status')->get()
            ->map(fn ($r) => ['label' => ucwords($r->name), 'value' => (int) $r->total])->values()->toArray();
    }

    private function paymentStatusBreakdown(): array
    {
        return Payment::selectRaw('status as name, COUNT(*) as total')
            ->groupBy('status')->get()
            ->map(fn ($r) => ['label' => ucwords(str_replace('_', ' ', $r->name)), 'value' => (int) $r->total])->values()->toArray();
    }

    private function newClientsTrend(int $months): array
    {
        $labels = [];
        $values = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $start = now()->startOfMonth()->subMonths($i);
            $end = now()->startOfMonth()->subMonths($i)->endOfMonth();
            $labels[] = $start->format('M');
            $values[] = Client::whereBetween('created_at', [$start, $end])->count();
        }
        return ['labels' => $labels, 'values' => $values];
    }

    private function myCompletedTrend(int $userId, int $months): array
    {
        $labels = [];
        $values = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $start = now()->startOfMonth()->subMonths($i);
            $end = now()->startOfMonth()->subMonths($i)->endOfMonth();
            $labels[] = $start->format('M');
            $values[] = Task::whereHas('assignments', fn ($q) => $q->where('user_id', $userId))
                ->where('status', 'completed')->whereBetween('completed_at', [$start, $end])->count();
        }
        return ['labels' => $labels, 'values' => $values];
    }

    private function pipelineCounts(): array
    {
        return \App\Services\PipelineStats::counts();
    }

    private function myTaskStatusBreakdown(int $userId): array
    {
        return Task::whereHas('assignments', fn ($q) => $q->where('user_id', $userId))
            ->selectRaw('status as name, COUNT(*) as total')
            ->groupBy('status')->get()
            ->map(fn ($r) => ['label' => ucwords(str_replace('_', ' ', $r->name)), 'value' => (int) $r->total])->values()->toArray();
    }
}
