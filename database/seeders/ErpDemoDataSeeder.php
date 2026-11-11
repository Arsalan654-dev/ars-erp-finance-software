<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\ClientContact;
use App\Models\ClientNote;
use App\Models\ClientService;
use App\Models\ComplianceRecord;
use App\Models\Employee;
use App\Models\EmployeeKpiResult;
use App\Models\ExchangeRate;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\KpiDefinition;
use App\Models\KpiTarget;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\Notification;
use App\Models\PayrollRecord;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\TaskComment;
use App\Models\TaskStatusHistory;
use App\Models\TrainingAssignment;
use App\Models\TrainingCourse;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ErpDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Seeding comprehensive demo data...');

        // ──────────────────────────────────────────────
        // 1. USERS (add 5 more → 10 total, 2 per role)
        // ──────────────────────────────────────────────
        $roles = [
            'super_admin' => \App\Models\Role::where('name', 'super_admin')->first(),
            'hr' => \App\Models\Role::where('name', 'hr')->first(),
            'finance' => \App\Models\Role::where('name', 'finance')->first(),
            'csr_manager' => \App\Models\Role::where('name', 'csr_manager')->first(),
            'team_associate' => \App\Models\Role::where('name', 'team_associate')->first(),
        ];

        $extraUsers = [
            ['name' => 'Senior HR Officer', 'email' => 'hr2@consultiser.com', 'password' => 'hr12345', 'phone' => '+1-555-0201', 'department' => 'Human Resources', 'designation' => 'Senior HR Officer', 'role' => 'hr'],
            ['name' => 'Accounts Executive', 'email' => 'finance2@consultiser.com', 'password' => 'finance123', 'phone' => '+1-555-0202', 'department' => 'Finance', 'designation' => 'Accounts Executive', 'role' => 'finance'],
            ['name' => 'Operations Lead', 'email' => 'csr2@consultiser.com', 'password' => 'csr12345', 'phone' => '+1-555-0203', 'department' => 'Operations', 'designation' => 'Operations Lead', 'role' => 'csr_manager'],
            ['name' => 'US Tax Associate', 'email' => 'associate2@consultiser.com', 'password' => 'assoc123', 'phone' => '+1-555-0204', 'department' => 'US TAX', 'designation' => 'US Tax Associate', 'role' => 'team_associate'],
            ['name' => 'UK Tax Associate', 'email' => 'associate3@consultiser.com', 'password' => 'assoc123', 'phone' => '+1-555-0205', 'department' => 'UK TAX', 'designation' => 'UK Tax Associate', 'role' => 'team_associate'],
        ];

        foreach ($extraUsers as $u) {
            $user = User::firstOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'password' => Hash::make($u['password']),
                    'phone' => $u['phone'],
                    'department' => $u['department'],
                    'designation' => $u['designation'],
                    'employee_id' => 'EMP-'.str_pad((string) rand(11, 99), 4, '0', STR_PAD_LEFT),
                ]
            );
            $user->roles()->sync([$roles[$u['role']]->id]);
        }

        // Reference users
        $admin = User::where('email', 'admin@consultiser.com')->first();
        $hr = User::where('email', 'hr@consultiser.com')->first();
        $finance = User::where('email', 'finance@consultiser.com')->first();
        $csr = User::where('email', 'csr@consultiser.com')->first();
        $associate = User::where('email', 'associate@consultiser.com')->first();
        $hr2 = User::where('email', 'hr2@consultiser.com')->first();
        $finance2 = User::where('email', 'finance2@consultiser.com')->first();
        $csr2 = User::where('email', 'csr2@consultiser.com')->first();
        $associate2 = User::where('email', 'associate2@consultiser.com')->first();
        $associate3 = User::where('email', 'associate3@consultiser.com')->first();

        $team = [$associate, $associate2, $associate3];

        // ──────────────────────────────────────────────
        // 2. EMPLOYEES (one per user)
        // ──────────────────────────────────────────────
        $empRows = [
            [$admin, 'Super', 'Admin'],
            [$hr, 'HR', 'Manager'],
            [$finance, 'Finance', 'Officer'],
            [$csr, 'CSR', 'Manager'],
            [$associate, 'Team', 'Associate'],
            [$hr2, 'Senior', 'HR'],
            [$finance2, 'Accounts', 'Executive'],
            [$csr2, 'Operations', 'Lead'],
            [$associate2, 'US Tax', 'Associate'],
            [$associate3, 'UK Tax', 'Associate'],
        ];

        $employees = [];
        foreach ($empRows as [$user, $first, $last]) {
            $emp = Employee::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'first_name' => $first,
                    'last_name' => $last,
                    'phone' => $user->phone,
                    'address' => '12 Corporate Park, Lahore',
                    'date_of_birth' => now()->subYears(rand(24, 45))->subDays(rand(0, 300)),
                    'joining_date' => now()->subMonths(rand(6, 24)),
                    'employment_type' => 'full_time',
                    'status' => 'active',
                ]
            );
            $employees[$user->id] = $emp;
        }

        // ──────────────────────────────────────────────
        // 3. EXCHANGE RATES
        // ──────────────────────────────────────────────
        $rateData = [
            ['USD', 'PKR', 278.50, now()->subDays(2)],
            ['GBP', 'PKR', 356.20, now()->subDays(2)],
            ['EUR', 'PKR', 302.40, now()->subDays(2)],
            ['USD', 'PKR', 277.90, now()->subDays(20)],
            ['GBP', 'PKR', 354.80, now()->subDays(20)],
            ['EUR', 'PKR', 301.10, now()->subDays(20)],
        ];
        foreach ($rateData as [$from, $to, $rate, $date]) {
            ExchangeRate::updateOrCreate(
                ['from_currency' => $from, 'to_currency' => $to, 'date' => $date->toDateString()],
                ['rate' => $rate, 'source' => 'manual', 'set_by' => $finance->id]
            );
        }

        // ──────────────────────────────────────────────
        // 4. CLIENTS (20+)
        // ──────────────────────────────────────────────
        $clientSeeds = [
            ['BlueWave Consulting Ltd', 'UK', 'GBP', 'active'],
            ['Summit Valley Partners', 'US', 'USD', 'active'],
            ['Aurora Tech Solutions', 'US', 'USD', 'active'],
            ['Nordic Trade Group', 'UK', 'GBP', 'active'],
            ['Everest Financial Services', 'US', 'USD', 'active'],
            ['Crown & Shield Legal', 'UK', 'GBP', 'active'],
            ['Horizon Export House', 'AE', 'USD', 'lead'],
            ['Maple Leaf Trading Co', 'CA', 'USD', 'active'],
            ['Silver Oak Holdings', 'US', 'USD', 'active'],
            ['Thames River Advisory', 'UK', 'GBP', 'active'],
            ['Pacific Crest Imports', 'US', 'USD', 'active'],
            ['Lakeside Business Group', 'US', 'USD', 'active'],
            ['Royal Peak Enterprises', 'US', 'USD', 'active'],
            ['Dune Consulting FZE', 'AE', 'USD', 'lead'],
            ['Alpine Ventures GmbH', 'DE', 'EUR', 'active'],
            ['Harbor Point Traders', 'US', 'USD', 'active'],
            ['Golden Gate Partners', 'US', 'USD', 'active'],
            ['Kings Court Advisory', 'UK', 'GBP', 'active'],
            ['Stonebridge Holdings', 'US', 'USD', 'active'],
            ['Meridian Business Ltd', 'UK', 'GBP', 'active'],
            ['Obsidian Capital LLC', 'US', 'USD', 'active'],
            ['Redwood Commerce Group', 'US', 'USD', 'active'],
        ];

        $clients = [];
        foreach ($clientSeeds as $i => [$name, $country, $defaultCur, $status]) {
            $email = strtolower(str_replace([' ', '&', "'"], ['.', '', ''], $name)).'@demo.com';
            $client = Client::firstOrCreate(
                ['business_name' => $name],
                [
                    'legal_name' => $name.' '.($country === 'UK' ? 'Limited' : 'LLC'),
                    'email' => $email,
                    'phone' => '+'.(($country === 'UK') ? '44' : '1').'-'.rand(200, 899).'-'.rand(100, 999).'-'.rand(1000, 9999),
                    'country' => $country,
                    'address' => rand(1, 999).' Business Avenue, '.($country === 'UK' ? 'London' : 'New York'),
                    'ein' => $country === 'US' ? rand(10, 99).'-'.rand(1000000, 9999999) : null,
                    'itin' => null,
                    'utr' => $country === 'UK' ? (string) rand(1000000000, 9999999999) : null,
                    'ssn' => null,
                    'tax_type' => $country === 'UK' ? 'UK Corporation Tax' : ($country === 'US' ? 'US Federal Tax' : 'VAT Registered'),
                    'status' => $status,
                    'created_by' => $csr->id,
                ]
            );
            $clients[] = $client;

            ClientContact::firstOrCreate(
                ['client_id' => $client->id, 'email' => 'contact@'.strtolower(str_replace([' ', '&', "'"], '.', $name)).'.demo'],
                ['first_name' => ['James', 'Maria', 'David', 'Sophia', 'Robert'][rand(0, 4)], 'last_name' => ['Smith', 'Jones', 'Brown', 'Taylor', 'Wilson'][rand(0, 4)], 'phone' => $client->phone, 'position' => ['Director', 'CFO', 'Manager', 'CEO'][rand(0, 3)], 'is_primary' => true]
            );

            ClientService::firstOrCreate(
                ['client_id' => $client->id, 'service_name' => $defaultCur === 'GBP' ? 'UK Tax Filing' : ($defaultCur === 'EUR' ? 'EU VAT Compliance' : 'US Tax Filing')],
                ['status' => 'active', 'start_date' => now()->subMonths(rand(2, 18))]
            );

            ClientNote::firstOrCreate(
                ['client_id' => $client->id, 'user_id' => $csr->id, 'content' => 'Onboarding complete — documentation verified in vault.'],
                ['type' => 'general']
            );
        }

        // ──────────────────────────────────────────────
        // 5. QUOTATIONS (20+)
        // ──────────────────────────────────────────────
        $qStatuses = ['draft', 'sent', 'accepted', 'expired', 'converted'];
        for ($i = 0; $i < 22; $i++) {
            $client = $clients[$i % count($clients)];
            $currency = $client->country === 'UK' ? 'GBP' : ($client->country === 'DE' ? 'EUR' : 'USD');
            $subtotal = rand(1200, 15000);
            $tax = round($subtotal * 0.16, 2);
            $total = $subtotal + $tax;
            $num = 'QTN-'.str_pad((string) (1000 + $i + 1), 5, '0', STR_PAD_LEFT);

            $quotation = Quotation::firstOrCreate(
                ['quotation_number' => $num],
                [
                    'client_id' => $client->id,
                    'currency' => $currency,
                    'subtotal' => $subtotal,
                    'discount' => 0,
                    'tax' => $tax,
                    'total' => $total,
                    'status' => $qStatuses[$i % 5],
                    'valid_until' => now()->addDays(rand(15, 60)),
                    'notes' => 'Generated from demo data.',
                    'terms' => 'Payment due within 30 days. Prices valid until validity date.',
                    'created_by' => $csr->id,
                ]
            );
            QuotationItem::firstOrCreate(
                ['quotation_id' => $quotation->id, 'description' => ['Tax Return Preparation', 'Compliance Filing', 'Bookkeeping Retainer', 'Audit Support', 'Company Registration'][$i % 5]],
                ['quantity' => rand(1, 3), 'unit_price' => round($subtotal / 2, 2), 'total' => $subtotal]
            );
        }

        // ──────────────────────────────────────────────
        // 6. INVOICES (20+)
        // ──────────────────────────────────────────────
        $invStatuses = ['sent', 'locked', 'partially_paid', 'paid', 'cancelled'];
        $createdInvoices = [];
        for ($i = 0; $i < 22; $i++) {
            $client = $clients[$i % count($clients)];
            $currency = $client->country === 'UK' ? 'GBP' : ($client->country === 'DE' ? 'EUR' : 'USD');
            $total = rand(1500, 20000);
            $status = $invStatuses[$i % 5];
            $paid = $status === 'paid' ? $total : ($status === 'partially_paid' ? round($total / 2, 2) : 0);
            $num = 'INV-'.str_pad((string) (1000 + $i + 1), 5, '0', STR_PAD_LEFT);

            $invoice = Invoice::firstOrCreate(
                ['invoice_number' => $num],
                [
                    'client_id' => $client->id,
                    'currency' => $currency,
                    'subtotal' => $total,
                    'discount' => 0,
                    'tax' => 0,
                    'total' => $total,
                    'paid_amount' => $paid,
                    'balance' => $total - $paid,
                    'status' => $status,
                    'payment_status' => $status === 'paid' ? 'paid' : ($status === 'partially_paid' ? 'partial' : 'unpaid'),
                    'issue_date' => now()->subDays(rand(5, 45)),
                    'due_date' => now()->addDays(rand(5, 45)),
                    'locked_at' => in_array($status, ['locked', 'partially_paid', 'paid']) ? now()->subDays(rand(3, 30)) : null,
                    'locked_by' => in_array($status, ['locked', 'partially_paid', 'paid']) ? $finance->id : null,
                    'notes' => null,
                    'terms' => 'Net 30 days.',
                    'created_by' => $csr->id,
                ]
            );
            InvoiceItem::firstOrCreate(
                ['invoice_id' => $invoice->id, 'description' => ['Professional Services', 'Tax Compliance', 'Consulting', 'Monthly Retainer', 'Filing Fee'][$i % 5]],
                ['quantity' => 1, 'unit_price' => $total, 'total' => $total]
            );
            $createdInvoices[] = $invoice;
        }

        // ──────────────────────────────────────────────
        // 7. PAYMENTS (verified + pending)
        // ──────────────────────────────────────────────
        foreach ($createdInvoices as $inv) {
            if (in_array($inv->status, ['paid', 'partially_paid'])) {
                $this->createPayment($inv, $inv->paid_amount, $finance, $admin, now()->subDays(rand(1, 30)));
            }
        }
        // A couple of pending verifications for the queue
        foreach (array_slice($createdInvoices, 0, 3) as $inv) {
            $this->createPayment($inv, round($inv->total * 0.4, 2), $finance, null, now(), false);
        }

        // ──────────────────────────────────────────────
        // 8. TASKS (22 — mix of statuses + unassigned pool items)
        // ──────────────────────────────────────────────
        $dept = ['US TAX', 'UK TAX', 'LEGAL'];
        $priorities = ['low', 'medium', 'high', 'urgent'];
        $taskStatuses = ['pending', 'assigned', 'in_progress', 'review', 'completed', 'cancelled'];
        $taskTitles = [
            'Prepare US federal tax return', 'File UK corporation tax return', 'Review compliance documents', 'Company registration renewal', 'VAT return preparation',
            'Annual report submission', 'Client KYC verification', 'Prepare tax extension request', 'Audit support — gather ledgers', 'Update SOP documentation',
            'Bank reconciliation for client', 'Prepare quarterly payroll', 'Draft legal contract', 'Review partnership tax return', 'Process refund application',
            'Update compliance register', 'Prepare board meeting minutes', 'File dormant company accounts', 'Review expense claims', 'Prepare financial statement pack',
            'Renew business license', 'Client onboarding documentation',
        ];

        $tasks = [];
        foreach ($taskTitles as $i => $title) {
            $client = $clients[$i % count($clients)];
            $status = $taskStatuses[$i % 6];
            // Leave tasks at the end unassigned so the CSR distribution pool has items
            $unassigned = $i >= 16;
            if ($unassigned) {
                $status = 'pending';
            }
            $num = 'TSK-'.str_pad((string) (1000 + $i + 1), 5, '0', STR_PAD_LEFT);

            $task = Task::firstOrCreate(
                ['task_number' => $num],
                [
                    'client_id' => $client->id,
                    'title' => $title,
                    'description' => 'Demo task: '.$title.' for '.$client->business_name.'. Reference documents are in the vault.',
                    'department' => $dept[$i % 3],
                    'priority' => $priorities[$i % 4],
                    'status' => $status,
                    'start_date' => now()->subDays(rand(1, 10)),
                    'due_date' => $i % 5 === 0 ? now()->subDays(2) : now()->addDays(rand(1, 14)),
                    'completed_at' => $status === 'completed' ? now()->subDays(rand(1, 5)) : null,
                    'created_by' => $csr->id,
                    'completed_by' => $status === 'completed' ? $team[$i % 3]->id : null,
                ]
            );
            $tasks[] = $task;

            // Assign tasks to a team member unless it's a pool (unassigned) task
            if (! $unassigned) {
                $assignee = $team[$i % 3];
                TaskAssignment::firstOrCreate(
                    ['task_id' => $task->id, 'user_id' => $assignee->id],
                    ['assigned_by' => $csr->name, 'assigned_at' => now()->subDays(rand(1, 7))]
                );
            }

            TaskStatusHistory::firstOrCreate(
                ['task_id' => $task->id, 'from_status' => null, 'to_status' => $status],
                ['changed_by' => $csr->id]
            );

            if (! $unassigned && $i % 3 === 0) {
                TaskComment::firstOrCreate(
                    ['task_id' => $task->id, 'user_id' => $team[$i % 3]->id, 'content' => 'Started working on this. Awaiting client documents.'],
                );
            }
        }

        // ──────────────────────────────────────────────
        // 9. COMPLIANCE RECORDS (22 — spread of expiry windows)
        // ──────────────────────────────────────────────
        $docTypes = ['Business License', 'Tax Registration', 'VAT Certificate', 'Trade License', 'Professional Indemnity', 'Insurance Certificate', 'Company Registration', 'Filing Confirmation'];
        $authorities = ['IRS', 'HMRC', 'FBR', 'SECP', 'Companies House', 'Local Authority'];
        // Mix of expiry offsets: overdue(-10), 10d, 25d, 45d, 70d, 200d
        $expiryOffsets = [-10, 10, 10, 25, 25, 45, 45, 70, 70, 200, 300];
        for ($i = 0; $i < 22; $i++) {
            $client = $clients[$i % count($clients)];
            $offset = $expiryOffsets[$i % count($expiryOffsets)];
            $expiry = $offset < 0 ? now()->subDays(abs($offset)) : now()->addDays($offset);
            ComplianceRecord::firstOrCreate(
                ['client_id' => $client->id, 'document_type' => $docTypes[$i % 8], 'title' => $client->business_name.' — '.$docTypes[$i % 8]],
                [
                    'reference_number' => 'REF-'.str_pad((string) (1000 + $i + 1), 5, '0', STR_PAD_LEFT),
                    'issuing_authority' => $authorities[$i % 6],
                    'issue_date' => now()->subYear(),
                    'expiry_date' => $expiry,
                    'status' => $offset < 0 ? 'expired' : ($offset <= 30 ? 'expiring_soon' : 'active'),
                    'notes' => null,
                    'created_by' => $csr->id,
                ]
            );
        }

        // ──────────────────────────────────────────────
        // 10. TRAINING COURSES + ASSIGNMENTS
        // ──────────────────────────────────────────────
        $courseSeeds = [
            ['US Tax Fundamentals', 'US TAX', 8],
            ['UK Corporation Tax Basics', 'UK TAX', 6],
            ['Data Protection & GDPR', 'LEGAL', 4],
            ['Client Onboarding Process', 'OPERATIONS', 3],
            ['Advanced Excel for Finance', 'FINANCE', 5],
        ];
        $courseIds = [];
        foreach ($courseSeeds as [$title, $cat, $hours]) {
            $course = TrainingCourse::firstOrCreate(
                ['title' => $title],
                ['category' => $cat, 'duration_hours' => $hours, 'status' => 'active']
            );
            $courseIds[] = $course->id;
        }

        foreach ($employees as $emp) {
            $courseId = $courseIds[array_rand($courseIds)];
            $done = (bool) rand(0, 1);
            TrainingAssignment::firstOrCreate(
                ['training_course_id' => $courseId, 'employee_id' => $emp->id],
                [
                    'assigned_date' => now()->subDays(rand(5, 30)),
                    'status' => $done ? 'completed' : 'in_progress',
                    'completed_date' => $done ? now()->subDays(rand(1, 8)) : null,
                    'score' => $done ? rand(65, 100) : null,
                ]
            );
        }

        // ──────────────────────────────────────────────
        // 11. ATTENDANCE (last 15 working days incl. today)
        // ──────────────────────────────────────────────
        foreach ($employees as $emp) {
            for ($d = 0; $d < 15; $d++) {
                $date = now()->subDays($d);
                if ($date->isWeekend()) {
                    continue;
                }
                $statusRoll = rand(0, 20);
                $status = $statusRoll > 1 ? 'present' : ($statusRoll === 1 ? 'absent' : 'late');
                $clockIn = sprintf('09:%02d:00', rand(0, 25));
                $clockOut = sprintf('17:%02d:00', rand(0, 30));
                AttendanceRecord::firstOrCreate(
                    ['employee_id' => $emp->id, 'date' => $date->toDateString()],
                    [
                        'clock_in' => $status === 'absent' ? null : $clockIn,
                        'clock_out' => $status === 'absent' ? null : $clockOut,
                        'status' => $status,
                        'notes' => $status === 'absent' ? 'On leave' : null,
                    ]
                );
            }
        }

        // ──────────────────────────────────────────────
        // 12. LEAVE REQUESTS
        // ──────────────────────────────────────────────
        $leaveTypes = ['annual', 'sick', 'personal'];
        foreach ($employees as $emp) {
            for ($l = 0; $l < 2; $l++) {
                $type = $leaveTypes[$l % 3];
                $days = rand(1, 3);
                $start = now()->subMonths(rand(0, 2))->addDays(rand(2, 20));
                $leaveStatus = ['pending', 'approved', 'rejected'][rand(0, 2)];
                \App\Models\LeaveRequest::firstOrCreate(
                    ['employee_id' => $emp->id, 'start_date' => $start->toDateString()],
                    [
                        'type' => $type,
                        'end_date' => $start->copy()->addDays($days - 1)->toDateString(),
                        'total_days' => $days,
                        'reason' => $type === 'sick' ? 'Medical leave' : 'Personal leave',
                        'status' => $leaveStatus,
                        'approved_by' => $leaveStatus === 'pending' ? null : $hr->id,
                        'approved_at' => $leaveStatus === 'pending' ? null : now()->subDays(rand(1, 5)),
                        'rejection_reason' => $leaveStatus === 'rejected' ? 'Insufficient notice period' : null,
                    ]
                );
            }
        }

        // ──────────────────────────────────────────────
        // 13. PAYROLL (current + past 2 months)
        // ──────────────────────────────────────────────
        $periods = [now()->format('Y-m'), now()->subMonth()->format('Y-m'), now()->subMonths(2)->format('Y-m')];
        foreach ($employees as $emp) {
            foreach ($periods as $pi => $period) {
                $gross = rand(90000, 250000);
                $deductions = round($gross * 0.07, 2);
                PayrollRecord::firstOrCreate(
                    ['employee_id' => $emp->id, 'period' => $period],
                    [
                        'currency' => 'PKR',
                        'gross_amount' => $gross,
                        'deductions' => $deductions,
                        'net_amount' => $gross - $deductions,
                        'exchange_rate' => 1,
                        'pkr_equivalent' => $gross - $deductions,
                        'status' => $pi === 0 ? 'pending' : 'approved',
                        'payment_date' => $pi === 0 ? null : now()->subMonths($pi),
                        'notes' => null,
                        'created_by' => $hr->id,
                    ]
                );
            }
        }

        // ──────────────────────────────────────────────
        // 14. EXPENSES (15)
        // ──────────────────────────────────────────────
        $expCats = ['Office Supplies', 'Software', 'Travel', 'Utilities', 'Professional Fees', 'Marketing'];
        for ($i = 0; $i < 15; $i++) {
            Expense::firstOrCreate(
                ['expense_number' => 'EXP-'.str_pad((string) (100 + $i + 1), 5, '0', STR_PAD_LEFT)],
                [
                    'category' => $expCats[$i % 6],
                    'currency' => 'PKR',
                    'amount' => rand(5000, 80000),
                    'exchange_rate' => 1,
                    'pkr_equivalent' => rand(5000, 80000),
                    'expense_date' => now()->subDays(rand(1, 45)),
                    'description' => 'Demo '.$expCats[$i % 6].' expense',
                    'status' => $i % 4 === 0 ? 'pending' : 'approved',
                    'created_by' => $finance->id,
                    'approved_by' => $i % 4 === 0 ? null : $admin->id,
                    'approved_at' => $i % 4 === 0 ? null : now()->subDays(rand(1, 10)),
                ]
            );
        }

        // ──────────────────────────────────────────────
        // 15. LEDGER ENTRIES (revenue + expenses)
        // ──────────────────────────────────────────────
        $revenueAccount = LedgerAccount::where('code', '4000')->first() ?? LedgerAccount::where('type', 'income')->first();
        $expenseAccount = LedgerAccount::where('code', '5000')->first() ?? LedgerAccount::where('type', 'expense')->first();
        if ($revenueAccount) {
            foreach ($createdInvoices as $inv) {
                LedgerEntry::firstOrCreate(
                    ['invoice_id' => $inv->id, 'entry_type' => 'credit'],
                    [
                        'ledger_account_id' => $revenueAccount->id,
                        'amount' => $inv->total,
                        'currency' => $inv->currency,
                        'description' => 'Invoice '.$inv->invoice_number,
                        'entry_date' => $inv->issue_date?->toDateString() ?? now()->toDateString(),
                        'created_by' => $finance->id,
                    ]
                );
            }
        }
        if ($expenseAccount) {
            foreach (Expense::limit(6)->get() as $exp) {
                LedgerEntry::firstOrCreate(
                    ['expense_id' => $exp->id, 'entry_type' => 'debit'],
                    [
                        'ledger_account_id' => $expenseAccount->id,
                        'amount' => $exp->pkr_equivalent ?? $exp->amount,
                        'currency' => 'PKR',
                        'description' => 'Expense '.$exp->expense_number,
                        'entry_date' => $exp->expense_date?->toDateString() ?? now()->toDateString(),
                        'created_by' => $finance->id,
                    ]
                );
            }
        }

        // ──────────────────────────────────────────────
        // 16. NOTIFICATIONS
        // ──────────────────────────────────────────────
        $notifUsers = [$admin, $hr, $finance, $csr, $associate, $associate2, $associate3];
        foreach ($notifUsers as $u) {
            if (! $u) {
                continue;
            }
            Notification::firstOrCreate(
                ['user_id' => $u->id, 'title' => 'Welcome to Consultiser ERP'],
                ['type' => 'system', 'body' => 'Your account is active. Explore your dashboard.', 'is_read' => true]
            );
            if (rand(0, 1)) {
                Notification::firstOrCreate(
                    ['user_id' => $u->id, 'title' => 'Task deadline approaching'],
                    ['type' => 'task', 'body' => 'You have a task due soon. Please review.', 'link' => route('erp.tasks.index', [], false), 'is_read' => false]
                );
            }
        }

        // ──────────────────────────────────────────────
        // 17. KPI TARGETS + RESULTS
        // ──────────────────────────────────────────────
        $kpiDefs = KpiDefinition::all();
        $period = now()->format('Y-m');
        foreach ($team as $u) {
            if (! $u) {
                continue;
            }
            foreach ($kpiDefs as $kpi) {
                $target = rand(5, 30);
                KpiTarget::firstOrCreate(
                    ['kpi_definition_id' => $kpi->id, 'user_id' => $u->id, 'period' => $period],
                    ['target_value' => $target]
                );
                EmployeeKpiResult::firstOrCreate(
                    ['kpi_definition_id' => $kpi->id, 'user_id' => $u->id, 'period' => $period],
                    ['actual_value' => rand(2, 28), 'target_value' => $target, 'score' => rand(45, 100)]
                );
            }
        }

        // ──────────────────────────────────────────────
        // 18. AUDIT LOGS
        // ──────────────────────────────────────────────
        $auditActions = ['USER_LOGIN', 'CLIENT_CREATE', 'INVOICE_SENT', 'TASK_STATUS_CHANGE', 'PAYMENT_VERIFY', 'DOCUMENT_UPLOAD', 'COMPLIANCE_RENEW', 'SETTINGS_UPDATE'];
        $auditEntities = ['client', 'invoice', 'task', 'payment', 'document', 'compliance'];
        for ($i = 0; $i < 40; $i++) {
            AuditLog::firstOrCreate(
                ['user_id' => $admin->id, 'action' => $auditActions[$i % 8], 'entity_type' => $auditEntities[$i % 6], 'entity_id' => ($i % 22) + 1],
                [
                    'ip_address' => '192.168.1.'.rand(10, 250),
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/152.0',
                    'metadata' => ['demo' => true],
                ]
            );
        }

        $this->command->info('✅ Demo data seeded successfully!');
        $this->command->info('');
        $this->command->info('━━━ LOGIN CREDENTIALS ━━━');
        $this->command->info('  Super Admin  →  admin@consultiser.com      /  admin123');
        $this->command->info('  HR Manager   →  hr@consultiser.com         /  hr12345');
        $this->command->info('  Finance      →  finance@consultiser.com    /  finance123');
        $this->command->info('  CSR Manager  →  csr@consultiser.com        /  csr12345');
        $this->command->info('  Associate    →  associate@consultiser.com  /  assoc123');
        $this->command->info('  HR (2nd)     →  hr2@consultiser.com        /  hr12345');
        $this->command->info('  Finance (2nd) → finance2@consultiser.com   /  finance123');
        $this->command->info('  CSR (2nd)    →  csr2@consultiser.com       /  csr12345');
        $this->command->info('  US Tax Asso  →  associate2@consultiser.com /  assoc123');
        $this->command->info('  UK Tax Asso  →  associate3@consultiser.com /  assoc123');
        $this->command->info('');
        $this->command->info('Seeded: '.count($clients).' clients, 22 quotations, 22 invoices, 22 tasks, 22 compliance, 10 employees, 30 payroll records, 15 expenses, KPI + audit data.');
    }

    /**
     * Small helper to keep payment creation DRY.
     */
    private function createPayment($invoice, $amount, $recordedBy, $verifiedBy, $date, $verified = true)
    {
        $rate = $invoice->currency === 'USD' ? 278.5 : ($invoice->currency === 'GBP' ? 356.2 : 302.4);
        $payment = \App\Models\Payment::firstOrCreate(
            ['payment_number' => 'PAY-'.str_pad((string) (1000 + $invoice->id), 5, '0', STR_PAD_LEFT)],
            [
                'client_id' => $invoice->client_id,
                'currency' => $invoice->currency,
                'amount' => $amount,
                'exchange_rate' => $rate,
                'pkr_equivalent' => round($amount * $rate, 2),
                'payment_method' => ['bank_transfer', 'check', 'wire', 'online'][rand(0, 3)],
                'reference_number' => 'REF-'.strtoupper(substr(md5($invoice->id.$amount), 0, 8)),
                'status' => $verified ? 'verified' : 'pending',
                'payment_date' => $date,
                'notes' => null,
                'recorded_by' => $recordedBy?->id,
                'verified_by' => $verifiedBy?->id,
                'verified_at' => $verified ? now() : null,
            ]
        );
        $payment->invoices()->syncWithoutDetaching([$invoice->id => ['amount' => $amount]]);

        return $payment;
    }
}