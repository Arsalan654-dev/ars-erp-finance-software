<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\EmailSetting;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Lead;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;
use App\Models\UserPermission;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AdminController extends Controller
{
    // ---- Users ----

    public function users()
    {
        $users = User::with('roles')->latest()->paginate(15);

        return view('erp.admin.users.index', compact('users'));
    }

    public function createUser()
    {
        $roles = Role::all();

        return view('erp.admin.users.create', compact('roles'));
    }

    public function storeUser(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string'],
            'department' => ['nullable', 'string'],
            'designation' => ['nullable', 'string'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
            'user_permissions' => ['nullable', 'array'],
            'user_permissions.*.permission_id' => ['nullable', 'exists:permissions,id'],
            'user_permissions.*.type' => ['nullable', 'in:allow,deny,'],
        ]);

        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);

        if ($request->has('roles')) {
            $user->roles()->sync($request->roles);
        }

        // Save per-user permission overrides (skip None selections)
        if ($request->has('user_permissions')) {
            foreach ($request->user_permissions as $override) {
                if (empty($override['permission_id']) || ! in_array($override['type'] ?? '', ['allow', 'deny'])) {
                    continue;
                }

                UserPermission::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'permission_id' => $override['permission_id'],
                    ],
                    ['type' => $override['type']]
                );
            }
        }

        AuditService::log('USER_CREATE', 'user', $user->id);

        return redirect()->route('erp.admin.users')->with('success', 'User created.');
    }

    public function editUser(User $user)
    {
        $roles = Role::all();

        return view('erp.admin.users.edit', compact('user', 'roles'));
    }

    public function updateUser(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string'],
            'department' => ['nullable', 'string'],
            'designation' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
            'user_permissions' => ['nullable', 'array'],
            'user_permissions.*.permission_id' => ['nullable', 'exists:permissions,id'],
            'user_permissions.*.type' => ['nullable', 'in:allow,deny,'],
        ]);

        // Protect last Super Admin
        $isCurrentlySuperAdmin = $user->hasRole('super_admin');
        $wouldRemoveSuperAdmin = isset($data['roles']) && ! in_array(
            Role::where('name', 'super_admin')->first()?->id ?? 0,
            $data['roles']
        );
        $wouldDeactivate = isset($data['is_active']) && ! $data['is_active'];

        if ($isCurrentlySuperAdmin && ($wouldRemoveSuperAdmin || $wouldDeactivate)) {
            $superAdminCount = User::whereHas('roles', fn ($q) => $q->where('name', 'super_admin'))->count();
            if ($superAdminCount <= 1) {
                return back()->with('error', 'Cannot deactivate or remove role from the last Super Admin.');
            }
        }

        $user->update($data);

        if ($request->has('roles')) {
            $user->roles()->sync($request->roles);
        }

        // Sync per-user permission overrides (allow/deny)
        // type === '' (None) → override removed (back to role default)
        $overrides = collect($request->user_permissions ?? [])
            ->filter(fn ($o) => ! empty($o['permission_id']))
            ->mapWithKeys(fn ($o) => [(int) $o['permission_id'] => $o['type'] ?? ''])
            ->filter(fn ($type) => in_array($type, ['allow', 'deny']))
            ->mapWithKeys(fn ($type, $permId) => [$permId => ['type' => $type]])
            ->all();

        $user->permissions()->sync($overrides);

        AuditService::log('USER_UPDATE', 'user', $user->id, [
            'changed_by' => auth()->id(),
        ]);

        return redirect()->route('erp.admin.users')->with('success', 'User updated.');
    }

    // ---- Roles & Permissions ----

    public function roles()
    {
        $roles = Role::withCount('users', 'permissions')->paginate(10);
        $permissions = Permission::all()->groupBy('group');
        $rolePermissions = Role::with('permissions:id')->get()->mapWithKeys(fn ($r) => [$r->id => $r->permissions->pluck('id')->all()]);

        // Per-role users (with their per-user permission overrides)
        $roleUsers = Role::with(['users' => function ($q) {
            $q->select('users.id', 'users.name', 'users.email');
        }])->get()->mapWithKeys(fn ($r) => [$r->id => $r->users]);

        // Per-user effective permissions keyed by slug → type (role / allow / deny)
        $userPermissions = User::with(['roles.permissions', 'permissions'])->get()->mapWithKeys(function ($u) {
            $map = [];
            foreach ($u->getAllRolePermissions() as $p) {
                $map[$p->slug] = 'role';
            }
            foreach ($u->permissions as $p) {
                $map[$p->slug] = $p->pivot->type;
            }

            return [$u->id => $map];
        });

        return view('erp.admin.roles.index', compact('roles', 'permissions', 'rolePermissions', 'roleUsers', 'userPermissions'));
    }

    /**
     * Update a specific user's per-user permission overrides.
     * Sent from the role's "Set Permissions" modal after choosing a user.
     */
    public function updateUserPermissions(Request $request, User $user)
    {
        $data = $request->validate([
            'user_permissions' => ['nullable', 'array'],
            'user_permissions.*.permission_id' => ['nullable', 'exists:permissions,id'],
            'user_permissions.*.type' => ['nullable', 'in:allow,deny,'],
        ]);

        $overrides = collect($data['user_permissions'] ?? [])
            ->filter(fn ($o) => ! empty($o['permission_id']))
            ->mapWithKeys(fn ($o) => [(int) $o['permission_id'] => $o['type'] ?? ''])
            ->filter(fn ($type) => in_array($type, ['allow', 'deny']))
            ->mapWithKeys(fn ($type, $permId) => [$permId => ['type' => $type]])
            ->all();

        $user->permissions()->sync($overrides);

        AuditService::log('USER_PERMISSIONS_UPDATE', 'user', $user->id, [
            'changed_by' => auth()->id(),
            'overrides' => $overrides,
        ]);

        return back()->with('success', "Permissions updated for {$user->name}.");
    }

    public function storeRole(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role = Role::create($data);
        $role->permissions()->sync($data['permissions'] ?? []);

        return back()->with('success', 'Role created.');
    }

    public function updateRole(Request $request, Role $role)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'unique:roles,name,'.$role->id],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role->update($data);
        $role->permissions()->sync($data['permissions'] ?? []);

        return back()->with('success', 'Role updated.');
    }

    public function destroyRole(Role $role)
    {
        if ($role->users()->exists()) {
            return back()->with('error', 'Cannot delete role — it is assigned to '.$role->users()->count().' user(s).');
        }

        $role->permissions()->detach();
        $role->delete();

        return back()->with('success', 'Role deleted.');
    }

    // ---- Audit Logs ----

    public function auditLogs(Request $request)
    {
        $query = AuditLog::with('user');

        if ($request->get('action')) {
            $query->where('action', 'like', '%'.$request->get('action').'%');
        }
        if ($request->get('entity_type')) {
            $query->where('entity_type', $request->get('entity_type'));
        }
        if ($request->get('user_id')) {
            $query->where('user_id', $request->get('user_id'));
        }

        $logs = $query->latest()->paginate(25)->withQueryString();

        return view('erp.admin.audit-logs', compact('logs'));
    }

    // ---- Admin Reports (super-admin overview) ----

    public function reports()
    {
        $counts = [
            'leads' => Lead::count(),
            'leads_follow_up' => Lead::where('status', 'follow_up')->count(),
            'leads_lost' => Lead::where('status', 'lost')->count(),
            'clients' => Client::count(),
            'quotations' => Quotation::count(),
            'quotations_duplicate' => Quotation::whereNotNull('duplicate_of_id')->count(),
            'invoices' => Invoice::count(),
            'invoice_descriptions_shared' => InvoiceItem::query()
                ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
                ->selectRaw('TRIM(LOWER(invoice_items.description)) as descr')
                ->whereNotNull('invoice_items.description')->where('invoice_items.description', '!=', '')
                ->groupBy('descr')
                ->havingRaw('COUNT(DISTINCT invoices.client_id) > 1')
                ->count(),
            'payments' => Payment::count(),
            'payments_flagged' => Payment::where('amount_mismatch', true)->count(),
            'payments_pending' => Payment::whereIn('status', ['pending', 'acknowledged'])->count(),
            'tasks' => Task::count(),
            'users' => User::count(),
        ];

        $flaggedPayments = Payment::with(['client', 'recorder'])
            ->where('amount_mismatch', true)
            ->latest()
            ->limit(20)
            ->get();

        $duplicateQuotations = Quotation::with(['client', 'duplicateOf'])
            ->whereNotNull('duplicate_of_id')
            ->latest()
            ->limit(20)
            ->get();

        return view('erp.admin.reports', compact('counts', 'flaggedPayments', 'duplicateQuotations'));
    }

    /**
     * Per-person work report (Point 19): how much work each user did, when,
     * and how much is still remaining (open assignments).
     */
    public function workReport(Request $request)
    {
        $users = User::where('is_active', true)->orderBy('name')->get();

        $from = $request->date('from')?->startOfDay();
        $to = $request->date('to')?->endOfDay();

        $rows = $users->map(function (User $user): array {
            $createdTasks = Task::where('created_by', $user->id)->count();
            $completedTasks = Task::where('created_by', $user->id)
                ->where('status', 'completed')
                ->count();
            $assignedOpen = Task::whereHas('assignments', function ($q) use ($user): void {
                $q->where('user_id', $user->id)->whereNull('unassigned_at');
            })
                ->whereIn('status', ['pending', 'assigned', 'in_progress', 'review'])
                ->count();

            $leads = Lead::where('created_by', $user->id)->count();
            $leadsAssigned = Lead::where('assigned_to', $user->id)->count();
            $leadsConverted = Lead::where('assigned_to', $user->id)
                ->where('status', 'converted')
                ->count();

            $quotations = Quotation::where('created_by', $user->id)->count();
            $invoices = Invoice::where('created_by', $user->id)->count();
            $payments = Payment::where('recorded_by', $user->id)->count();

            $lastAction = AuditLog::where('user_id', $user->id)->latest('created_at')->first();

            return [
                'user' => $user,
                'created_tasks' => $createdTasks,
                'completed_tasks' => $completedTasks,
                'open_assigned_tasks' => $assignedOpen,
                'leads' => $leads,
                'leads_assigned' => $leadsAssigned,
                'leads_converted' => $leadsConverted,
                'quotations' => $quotations,
                'invoices' => $invoices,
                'payments' => $payments,
                'work_done' => $completedTasks + $leadsConverted + $quotations + $invoices + $payments,
                'remaining' => $assignedOpen,
                'last_action_at' => $lastAction?->created_at,
            ];
        })->sortByDesc('work_done')->values();

        // Work performed inside the selected date range (period totals).
        $periodCounts = [
            'tasks_completed' => Task::where('status', 'completed')
                ->when($from, fn ($q) => $q->where('updated_at', '>=', $from))
                ->when($to, fn ($q) => $q->where('updated_at', '<=', $to))
                ->count(),
            'leads_created' => Lead::when($from, fn ($q) => $q->where('created_at', '>=', $from))
                ->when($to, fn ($q) => $q->where('created_at', '<=', $to))
                ->count(),
            'quotations_created' => Quotation::when($from, fn ($q) => $q->where('created_at', '>=', $from))
                ->when($to, fn ($q) => $q->where('created_at', '<=', $to))
                ->count(),
            'invoices_created' => Invoice::when($from, fn ($q) => $q->where('created_at', '>=', $from))
                ->when($to, fn ($q) => $q->where('created_at', '<=', $to))
                ->count(),
            'payments_recorded' => Payment::when($from, fn ($q) => $q->where('created_at', '>=', $from))
                ->when($to, fn ($q) => $q->where('created_at', '<=', $to))
                ->count(),
        ];

        return view('erp.admin.work-report', compact('rows', 'periodCounts', 'from', 'to'));
    }

    // ---- Notifications ----

    public function notifications()
    {
        $notifications = auth()->user()->notifications()->latest()->paginate(20);

        return view('erp.notifications.index', compact('notifications'));
    }

    public function markNotification(Notification $notification)
    {
        abort_unless($notification->user_id === auth()->id(), 403, 'This is not your notification.');

        $notification->update(['is_read' => true, 'read_at' => now()]);

        return back();
    }

    public function markAllRead()
    {
        auth()->user()->notifications()->where('is_read', false)->update(['is_read' => true, 'read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }

    // ---- Settings ----

    public function settings()
    {
        $settings = Setting::group('general');

        return view('erp.admin.settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'company_name' => ['nullable', 'string', 'max:255'],
            'company_email' => ['nullable', 'email', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:50'],
            'company_address' => ['nullable', 'string'],
            'default_currency' => ['nullable', 'in:USD,GBP,EUR,PKR'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'invoice_prefix' => ['nullable', 'string', 'max:10'],
            'task_escalation_enabled' => ['nullable', 'boolean'],
            'email_queue_enabled' => ['nullable', 'boolean'],
        ]);

        foreach ($data as $key => $value) {
            Setting::set($key, $value, 'general');
        }

        AuditService::log('SETTINGS_UPDATE', 'settings', null, ['keys' => array_keys($data)]);

        return back()->with('success', 'Settings updated.');
    }

    // ---- Email Settings ----

    public function emailSettings()
    {
        $emailSetting = EmailSetting::firstOrCreate([], [
            'name' => 'default',
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'username' => '',
            'password' => '',
            'encryption' => 'tls',
            'from_address' => config('mail.from.address', 'noreply@example.com'),
            'from_name' => config('mail.from.name', config('erp.app_name')),
            'is_active' => true,
        ]);

        return view('erp.admin.email-settings', compact('emailSetting'));
    }

    public function updateEmailSettings(Request $request)
    {
        $emailSetting = EmailSetting::firstOrCreate([], ['name' => 'default', 'is_active' => true]);

        $data = $request->validate([
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'encryption' => ['required', 'in:tls,ssl,none'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'from_address' => ['required', 'email', 'max:255'],
            'from_name' => ['required', 'string', 'max:255'],
        ]);

        // Only update password if provided (otherwise keep existing)
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $data['is_active'] = true;
        $emailSetting->update($data);

        // Clear config cache so changes take effect immediately
        \Artisan::call('config:clear');

        AuditService::log('EMAIL_SETTINGS_UPDATE', 'email_setting', $emailSetting->id, [
            'host' => $data['host'],
            'username' => $data['username'],
        ]);

        return back()->with('success', 'Email settings updated. All outgoing emails will now use these credentials.');
    }

    public function testEmailSettings(Request $request)
    {
        $emailSetting = EmailSetting::getActive();

        if (! $emailSetting) {
            return back()->with('error', 'No active email configuration found. Save settings first.');
        }

        try {
            Mail::raw(
                "This is a test email from ARS ERP Finance Software.\n\n".
                'Sent at: '.now()->format('Y-m-d H:i:s')."\n".
                "SMTP Host: {$emailSetting->host}\n".
                "Port: {$emailSetting->port}\n".
                "Encryption: {$emailSetting->encryption}\n".
                "Username: {$emailSetting->username}\n\n".
                'If you received this, your email configuration is working correctly.',
                function ($message) use ($emailSetting): void {
                    $message->to($emailSetting->from_address, 'Admin')
                        ->subject('Test Email — ARS ERP Finance Software');
                }
            );

            AuditService::log('EMAIL_TEST_SENT', 'email_setting', $emailSetting->id);

            return back()->with('success', 'Test email sent successfully! Check your inbox at '.$emailSetting->from_address);
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to send test email: '.$e->getMessage());
        }
    }
}
