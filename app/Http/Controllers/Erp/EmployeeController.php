<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollRecord;
use App\Models\SopDocument;
use App\Models\TrainingAssignment;
use App\Models\TrainingCourse;
use App\Services\AuditService;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with('user');

        if ($request->get('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->get('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', '%'.$request->get('q').'%')
                    ->orWhere('last_name', 'like', '%'.$request->get('q').'%');
            });
        }

        $employees = $query->paginate(15)->withQueryString();

        return view('erp.hr.index', compact('employees'));
    }

    public function create()
    {
        $users = \App\Models\User::where('is_active', true)->orderBy('name')->get();

        return view('erp.hr.create', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id', 'unique:employees,user_id'],
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'phone' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'emergency_contact' => ['nullable', 'string'],
            'emergency_phone' => ['nullable', 'string'],
            'date_of_birth' => ['nullable', 'date'],
            'joining_date' => ['nullable', 'date'],
            'employment_type' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
        ]);

        $employee = Employee::create($data);

        AuditService::log('EMPLOYEE_CREATE', 'employee', $employee->id);

        return redirect()->route('erp.hr.show', $employee)->with('success', 'Employee record created.');
    }

    public function show(Employee $employee)
    {
        $employee->load(['user', 'attendance' => fn ($q) => $q->latest('date')->limit(30), 'trainingAssignments.trainingCourse']);

        $payrollRecords = $employee->payrollRecords()->latest('period')->paginate(6, ['*'], 'payroll_page')->withQueryString();

        return view('erp.hr.show', compact('employee', 'payrollRecords'));
    }

    public function edit(Employee $employee)
    {
        $users = \App\Models\User::where('is_active', true)->orderBy('name')->get();

        return view('erp.hr.edit', compact('employee', 'users'));
    }

    public function update(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'phone' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'emergency_contact' => ['nullable', 'string'],
            'emergency_phone' => ['nullable', 'string'],
            'date_of_birth' => ['nullable', 'date'],
            'joining_date' => ['nullable', 'date'],
            'employment_type' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
        ]);

        $employee->update($data);

        return redirect()->route('erp.hr.show', $employee)->with('success', 'Employee updated.');
    }

    // ---- Attendance ----

    public function attendance(Request $request)
    {
        $date = $request->get('date', now()->toDateString());
        $records = AttendanceRecord::with('employee.user')
            ->whereDate('date', $date)
            ->paginate(20)
            ->withQueryString();

        return view('erp.hr.attendance.index', compact('records', 'date'));
    }

    public function markAttendance(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'date' => ['required', 'date'],
            'status' => ['required', 'in:present,absent,late,half_day,holiday'],
            'clock_in' => ['nullable', 'date_format:H:i'],
            'clock_out' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string'],
        ]);

        AttendanceRecord::updateOrCreate(
            ['employee_id' => $data['employee_id'], 'date' => $data['date']],
            $data
        );

        return back()->with('success', 'Attendance marked.');
    }

    // ---- Training ----

    public function training()
    {
        $courses = TrainingCourse::withCount('assignments')->latest()->paginate(15);

        return view('erp.hr.training.index', compact('courses'));
    }

    public function storeTraining(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string'],
            'duration_hours' => ['nullable', 'integer', 'min:1'],
        ]);

        TrainingCourse::create($data);

        return back()->with('success', 'Training course created.');
    }

    public function assignTraining(Request $request)
    {
        $data = $request->validate([
            'training_course_id' => ['required', 'exists:training_courses,id'],
            'employee_id' => ['required', 'exists:employees,id'],
        ]);

        $data['assigned_date'] = now();
        TrainingAssignment::create($data);

        return back()->with('success', 'Training assigned.');
    }

    // ---- SOPs ----

    public function sops()
    {
        $sops = SopDocument::latest()->paginate(15);

        return view('erp.hr.sops.index', compact('sops'));
    }

    public function storeSop(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string'],
            'category' => ['nullable', 'string'],
            'version' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'department' => ['nullable', 'string'],
        ]);

        $data['created_by'] = auth()->id();
        SopDocument::create($data);

        return back()->with('success', 'SOP created.');
    }

    // ---- Payroll ----

    public function payroll(Request $request)
    {
        $query = PayrollRecord::with('employee.user');

        if ($request->get('period')) {
            $query->where('period', $request->get('period'));
        }

        $payrolls = $query->latest('period')->paginate(15)->withQueryString();

        return view('erp.hr.payroll.index', compact('payrolls'));
    }

    public function storePayroll(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'period' => ['required', 'string'],
            'currency' => ['required', 'in:PKR,USD,GBP,EUR'],
            'gross_amount' => ['required', 'numeric', 'min:0'],
            'deductions' => ['nullable', 'numeric', 'min:0'],
            'net_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['created_by'] = auth()->id();
        $data['status'] = 'pending';

        $rate = \App\Services\LedgerService::rateToPkr($data['currency']);
        $data['exchange_rate'] = $rate;
        $data['pkr_equivalent'] = $rate ? round($data['net_amount'] * $rate, 2) : null;

        PayrollRecord::create($data);

        return back()->with('success', 'Payroll record created.');
    }

    public function approvePayroll(PayrollRecord $payroll)
    {
        if ($payroll->status === 'approved') {
            return back()->with('error', 'Payroll is already approved.');
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($payroll) {
                $payroll->update([
                    'status' => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);

                // Post to ledger
                \App\Services\LedgerService::post('5100', 'debit', $payroll->pkr_equivalent ?: $payroll->net_amount, 'PKR', [
                    'payroll_id' => $payroll->id,
                ], "Payroll {$payroll->period} - {$payroll->employee->first_name} {$payroll->employee->last_name}");
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', 'Payroll approval failed: ' . $e->getMessage());
        }

        AuditService::log('PAYROLL_APPROVE', 'payroll', $payroll->id, ['period' => $payroll->period]);

        return back()->with('success', 'Payroll approved. Ledger posted.');
    }

    public function payslip(PayrollRecord $payroll)
    {
        return \App\Services\PdfService::payslip($payroll)->stream("payslip-{$payroll->period}-{$payroll->id}.pdf");
    }

    public function completeTraining(Request $request, TrainingAssignment $assignment)
    {
        $data = $request->validate([
            'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $assignment->update([
            'status' => 'completed',
            'completed_date' => now(),
            'score' => $data['score'] ?? null,
        ]);

        AuditService::log('TRAINING_COMPLETE', 'training_assignment', $assignment->id);

        return back()->with('success', 'Training marked as completed.');
    }

    // ---- Leave Management ----

    public function leave(Request $request)
    {
        $query = LeaveRequest::with(['employee.user', 'approver']);

        if ($request->get('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->get('type')) {
            $query->where('type', $request->get('type'));
        }

        $leaves = $query->latest()->paginate(15)->withQueryString();
        $employees = Employee::with('user')->orderBy('first_name')->get();

        // Annual leave balance (simplified: 22 days/yr, subtract approved annual leaves this year)
        $balances = [];
        foreach ($employees as $emp) {
            $used = LeaveRequest::where('employee_id', $emp->id)
                ->where('type', 'annual')
                ->where('status', 'approved')
                ->whereYear('start_date', now()->year)
                ->sum('total_days');
            $balances[$emp->id] = ['used' => $used, 'remaining' => max(0, 22 - $used)];
        }

        return view('erp.hr.leave.index', compact('leaves', 'employees', 'balances'));
    }

    public function storeLeave(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'type' => ['required', 'in:annual,sick,personal,unpaid,other'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string'],
        ]);

        $start = \Carbon\Carbon::parse($data['start_date']);
        $end = \Carbon\Carbon::parse($data['end_date']);
        $data['total_days'] = $start->diffInDays($end) + 1;
        $data['status'] = 'pending';

        LeaveRequest::create($data);

        return back()->with('success', 'Leave request created.');
    }

    public function decideLeave(Request $request, LeaveRequest $leave)
    {
        $data = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'rejection_reason' => ['nullable', 'string'],
        ]);

        if ($data['action'] === 'approve') {
            $leave->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);
            AuditService::log('LEAVE_APPROVE', 'leave_request', $leave->id);
        } else {
            $leave->update([
                'status' => 'rejected',
                'rejection_reason' => $data['rejection_reason'] ?? null,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
            AuditService::log('LEAVE_REJECT', 'leave_request', $leave->id);
        }

        return back()->with('success', 'Leave request '.$data['action'].'d.');
    }
}