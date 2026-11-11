<?php

namespace Tests\Feature;

use App\Http\Middleware\SessionSecurity;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LedgerAccount;
use App\Models\PayrollRecord;
use App\Models\TrainingCourse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\InteractsWithRoles;
use Tests\TestCase;

class HrTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    private User $hrUser;

    private User $financeUser;

    private User $associateUser;

    private User $employeeUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hrUser = $this->createUserWithRole('hr');
        $this->financeUser = $this->createUserWithRole('finance');
        $this->associateUser = $this->createUserWithRole('team_associate');
        $this->employeeUser = $this->createUserWithRole('team_associate');
    }

    private function asHr()
    {
        $this->actingAs($this->hrUser);
        $this->withoutMiddleware(SessionSecurity::class);

        return $this;
    }

    public function test_hr_can_view_employees(): void
    {
        $this->actingAs($this->hrUser);
        $this->get('/erp/hr')->assertStatus(200);
    }

    public function test_finance_cannot_view_hr(): void
    {
        $this->actingAs($this->financeUser);
        $this->withoutMiddleware(SessionSecurity::class);
        $this->get('/erp/hr')->assertStatus(302)->assertSessionHas('permission_denied');
    }

    public function test_associate_cannot_view_hr(): void
    {
        $this->actingAs($this->associateUser);
        $this->withoutMiddleware(SessionSecurity::class);
        $this->get('/erp/hr')->assertStatus(302)->assertSessionHas('permission_denied');
    }

    public function test_create_employee(): void
    {
        $user = $this->createUserWithRole('team_associate');
        $this->asHr();
        $response = $this->post('/erp/hr', [
            'user_id' => $user->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '+1-555-0100',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('employees', [
            'user_id' => $user->id,
            'first_name' => 'John',
        ]);
    }

    public function test_show_employee(): void
    {
        $employee = Employee::create([
            'user_id' => $this->employeeUser->id,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        $this->actingAs($this->hrUser);
        $this->get("/erp/hr/{$employee->id}")->assertStatus(200);
    }

    public function test_update_employee(): void
    {
        $employee = Employee::create([
            'user_id' => $this->employeeUser->id,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        $this->asHr();
        $response = $this->patch("/erp/hr/{$employee->id}", [
            'first_name' => 'Updated',
            'last_name' => 'Name',
        ]);

        $response->assertRedirect();
        $this->assertEquals('Updated', $employee->fresh()->first_name);
    }

    public function test_mark_attendance(): void
    {
        $employee = Employee::create([
            'user_id' => $this->employeeUser->id,
            'first_name' => 'Test',
            'last_name' => 'Employee',
        ]);

        $this->asHr();
        $response = $this->post('/erp/hr/attendance', [
            'employee_id' => $employee->id,
            'date' => now()->toDateString(),
            'status' => 'present',
            'clock_in' => '09:00',
            'clock_out' => '17:00',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('attendance_records', [
            'employee_id' => $employee->id,
            'status' => 'present',
        ]);
    }

    public function test_attendance_update_or_create(): void
    {
        $employee = Employee::create([
            'user_id' => $this->employeeUser->id,
            'first_name' => 'Test',
            'last_name' => 'Employee',
        ]);

        $this->asHr();
        $this->post('/erp/hr/attendance', [
            'employee_id' => $employee->id,
            'date' => now()->toDateString(),
            'status' => 'present',
        ]);

        $this->post('/erp/hr/attendance', [
            'employee_id' => $employee->id,
            'date' => now()->toDateString(),
            'status' => 'late',
        ]);

        $count = AttendanceRecord::where('employee_id', $employee->id)
            ->whereDate('date', now()->toDateString())->count();
        $this->assertEquals(1, $count);
    }

    public function test_create_training_course(): void
    {
        $this->asHr();
        $response = $this->post('/erp/hr/training', [
            'title' => 'Safety Training',
            'description' => 'Workplace safety',
            'duration_hours' => 4,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('training_courses', ['title' => 'Safety Training']);
    }

    public function test_assign_training(): void
    {
        $employee = Employee::create([
            'user_id' => $this->employeeUser->id,
            'first_name' => 'Test',
            'last_name' => 'Employee',
        ]);
        $course = TrainingCourse::create(['title' => 'Safety Training']);

        $this->asHr();
        $response = $this->post('/erp/hr/training/assign', [
            'training_course_id' => $course->id,
            'employee_id' => $employee->id,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('training_assignments', [
            'training_course_id' => $course->id,
            'employee_id' => $employee->id,
        ]);
    }

    public function test_create_sop(): void
    {
        $this->asHr();
        $response = $this->post('/erp/hr/sops', [
            'title' => 'Onboarding SOP',
            'category' => 'HR',
            'content' => 'Step 1: Welcome...',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('sop_documents', ['title' => 'Onboarding SOP']);
    }

    public function test_create_payroll(): void
    {
        $employee = Employee::create([
            'user_id' => $this->employeeUser->id,
            'first_name' => 'Test',
            'last_name' => 'Employee',
        ]);

        $this->asHr();
        $response = $this->post('/erp/hr/payroll', [
            'employee_id' => $employee->id,
            'period' => '2026-09',
            'currency' => 'USD',
            'gross_amount' => 5000,
            'net_amount' => 4500,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('payroll_records', [
            'employee_id' => $employee->id,
            'period' => '2026-09',
            'status' => 'pending',
        ]);
    }

    public function test_approve_payroll(): void
    {
        $employee = Employee::create([
            'user_id' => $this->employeeUser->id,
            'first_name' => 'Test',
            'last_name' => 'Employee',
        ]);

        $payroll = PayrollRecord::create([
            'employee_id' => $employee->id,
            'period' => '2026-09',
            'currency' => 'USD',
            'gross_amount' => 5000,
            'net_amount' => 4500,
            'status' => 'pending',
            'created_by' => $this->hrUser->id,
        ]);

        $ledgerAccount = LedgerAccount::firstOrCreate(
            ['code' => '5100'],
            ['name' => 'Payroll Expense', 'type' => 'expense', 'is_active' => true]
        );

        $this->asHr();
        $this->post("/erp/hr/payroll/{$payroll->id}/approve")->assertRedirect();
        $this->assertEquals('approved', $payroll->fresh()->status);
    }

    public function test_cannot_approve_already_approved_payroll(): void
    {
        $employee = Employee::create([
            'user_id' => $this->employeeUser->id,
            'first_name' => 'Test',
            'last_name' => 'Employee',
        ]);

        $payroll = PayrollRecord::create([
            'employee_id' => $employee->id,
            'period' => '2026-09',
            'currency' => 'USD',
            'gross_amount' => 5000,
            'net_amount' => 4500,
            'status' => 'approved',
            'created_by' => $this->hrUser->id,
        ]);

        $this->asHr();
        $this->post("/erp/hr/payroll/{$payroll->id}/approve")->assertSessionHas('error');
    }

    public function test_create_leave_request(): void
    {
        $employee = Employee::create([
            'user_id' => $this->employeeUser->id,
            'first_name' => 'Test',
            'last_name' => 'Employee',
        ]);

        $this->asHr();
        $response = $this->post('/erp/hr/leave', [
            'employee_id' => $employee->id,
            'type' => 'annual',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-05',
            'reason' => 'Vacation',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => $employee->id,
            'status' => 'pending',
        ]);
    }

    public function test_approve_leave(): void
    {
        $employee = Employee::create([
            'user_id' => $this->employeeUser->id,
            'first_name' => 'Test',
            'last_name' => 'Employee',
        ]);

        $leave = LeaveRequest::create([
            'employee_id' => $employee->id,
            'type' => 'annual',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-05',
            'total_days' => 5,
            'status' => 'pending',
        ]);

        $this->asHr();
        $this->post("/erp/hr/leave/{$leave->id}/decide", [
            'action' => 'approve',
        ])->assertRedirect();

        $this->assertEquals('approved', $leave->fresh()->status);
    }

    public function test_reject_leave(): void
    {
        $employee = Employee::create([
            'user_id' => $this->employeeUser->id,
            'first_name' => 'Test',
            'last_name' => 'Employee',
        ]);

        $leave = LeaveRequest::create([
            'employee_id' => $employee->id,
            'type' => 'sick',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-01',
            'total_days' => 1,
            'status' => 'pending',
        ]);

        $this->asHr();
        $this->post("/erp/hr/leave/{$leave->id}/decide", [
            'action' => 'reject',
            'rejection_reason' => 'Insufficient notice',
        ])->assertRedirect();

        $this->assertEquals('rejected', $leave->fresh()->status);
        $this->assertEquals('Insufficient notice', $leave->fresh()->rejection_reason);
    }

    public function test_leave_end_date_after_start_date(): void
    {
        $employee = Employee::create([
            'user_id' => $this->employeeUser->id,
            'first_name' => 'Test',
            'last_name' => 'Employee',
        ]);

        $this->asHr();
        $response = $this->post('/erp/hr/leave', [
            'employee_id' => $employee->id,
            'type' => 'annual',
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-01',
        ]);

        $response->assertSessionHasErrors('end_date');
    }
}
