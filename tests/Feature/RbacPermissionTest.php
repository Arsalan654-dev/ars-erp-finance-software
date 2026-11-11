<?php

namespace Tests\Feature;

use App\Http\Middleware\SessionSecurity;
use App\Models\Client;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\InteractsWithRoles;
use Tests\TestCase;

class RbacPermissionTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    private function userWithPermission(string $permission): User
    {
        $role = Role::firstOrCreate(['name' => 'perm_'.$permission]);
        $perm = Permission::firstOrCreate(
            ['slug' => $permission],
            ['name' => $permission, 'group' => 'test']
        );
        $role->permissions()->sync([$perm->id]);

        return $this->createUserWithRole('perm_'.$permission);
    }

    private function userWithPermissions(array $permissions): User
    {
        $roleName = 'perm_'.implode('_', $permissions);
        $role = Role::firstOrCreate(['name' => $roleName]);
        foreach ($permissions as $slug) {
            $perm = Permission::firstOrCreate(
                ['slug' => $slug],
                ['name' => $slug, 'group' => explode('.', $slug)[0]]
            );
            $role->permissions()->syncWithoutDetaching([$perm->id]);
        }

        return $this->createUserWithRole($roleName);
    }

    private function getAdmin(): User
    {
        return $this->createUserWithRole('super_admin');
    }

    // --- Client permission boundaries ---

    public function test_client_read_can_index(): void
    {
        $this->actingAs($this->userWithPermission('client.read'));
        $this->get('/erp/clients')->assertStatus(200);
    }

    public function test_client_read_cannot_create_form(): void
    {
        $this->actingAs($this->userWithPermission('client.read'));
        $this->get('/erp/clients/create')->assertStatus(302)->assertSessionHas('permission_denied');
    }

    public function test_client_create_can_store(): void
    {
        $user = $this->userWithPermissions(['client.read', 'client.create']);
        $this->actingAs($user);
        $this->get('/erp/clients/create')->assertStatus(200);
    }

    public function test_client_read_cannot_delete(): void
    {
        $admin = $this->getAdmin();
        $client = Client::create(['business_name' => 'Test', 'status' => 'active', 'created_by' => $admin->id]);
        $this->actingAs($this->userWithPermission('client.read'));
        $this->delete("/erp/clients/{$client->id}")->assertStatus(302)->assertSessionHas('permission_denied');
    }

    // --- Invoice permission boundaries ---

    public function test_invoice_read_cannot_lock(): void
    {
        $admin = $this->getAdmin();
        $client = Client::create(['business_name' => 'Test', 'status' => 'active', 'created_by' => $admin->id]);
        $invoice = Invoice::create([
            'invoice_number' => 'INV-RBAC-001', 'client_id' => $client->id,
            'currency' => 'USD', 'subtotal' => 100, 'total' => 100, 'balance' => 100,
            'status' => 'draft', 'payment_status' => 'unpaid',
            'issue_date' => now()->toDateString(), 'created_by' => $admin->id,
        ]);
        $this->actingAs($this->userWithPermission('invoice.read'));
        $this->post("/erp/invoices/{$invoice->id}/lock")->assertStatus(302)->assertSessionHas('permission_denied');
    }

    public function test_invoice_lock_cannot_send(): void
    {
        $admin = $this->getAdmin();
        $client = Client::create(['business_name' => 'Test', 'status' => 'active', 'created_by' => $admin->id]);
        $invoice = Invoice::create([
            'invoice_number' => 'INV-RBAC-002', 'client_id' => $client->id,
            'currency' => 'USD', 'subtotal' => 100, 'total' => 100, 'balance' => 100,
            'status' => 'locked', 'payment_status' => 'unpaid',
            'issue_date' => now()->toDateString(), 'created_by' => $admin->id,
        ]);
        $this->actingAs($this->userWithPermission('invoice.lock'));
        $this->post("/erp/invoices/{$invoice->id}/send")->assertStatus(302)->assertSessionHas('permission_denied');
    }

    public function test_invoice_read_can_index(): void
    {
        $this->actingAs($this->userWithPermission('invoice.read'));
        $this->get('/erp/invoices')->assertStatus(200);
    }

    // --- Task permission boundaries ---

    public function test_task_read_cannot_delete(): void
    {
        $admin = $this->getAdmin();
        $client = Client::create(['business_name' => 'Test', 'status' => 'active', 'created_by' => $admin->id]);
        $task = Task::create([
            'title' => 'Test', 'task_number' => 'TSK-RBAC-001',
            'client_id' => $client->id, 'status' => 'pending', 'created_by' => $admin->id,
        ]);
        $this->actingAs($this->userWithPermission('task.read'));
        $this->delete("/erp/tasks/{$task->id}")->assertStatus(302)->assertSessionHas('permission_denied');
    }

    public function test_task_update_cannot_create_form(): void
    {
        $this->actingAs($this->userWithPermission('task.update'));
        $this->get('/erp/tasks/create')->assertStatus(302)->assertSessionHas('permission_denied');
    }

    // --- Document permission boundaries ---

    public function test_document_read_cannot_upload_form(): void
    {
        $this->actingAs($this->userWithPermission('document.read'));
        $this->get('/erp/documents/create')->assertStatus(302)->assertSessionHas('permission_denied');
    }

    public function test_document_upload_cannot_delete_document(): void
    {
        $admin = $this->getAdmin();
        $client = Client::create(['business_name' => 'Test', 'status' => 'active', 'created_by' => $admin->id]);
        $doc = Document::create([
            'client_id' => $client->id, 'filename' => 'x.pdf', 'original_filename' => 'x.pdf',
            'mime_type' => 'application/pdf', 'size' => 100, 'storage_path' => 'x.pdf',
            'storage_disk' => 'private', 'category' => 'Tax', 'uploaded_by' => $admin->id,
        ]);
        $this->actingAs($this->userWithPermission('document.upload'));
        $this->withoutMiddleware(SessionSecurity::class);
        $this->delete("/erp/documents/{$doc->id}")->assertStatus(302)->assertSessionHas('permission_denied');
    }

    // --- Cross-module denials ---

    public function test_hr_cannot_access_finance(): void
    {
        $this->actingAs($this->createUserWithRole('hr'));
        $this->get('/erp/finance/payments')->assertStatus(302)->assertSessionHas('permission_denied');
    }

    public function test_finance_cannot_access_hr(): void
    {
        $this->actingAs($this->createUserWithRole('finance'));
        $this->get('/erp/hr')->assertStatus(302)->assertSessionHas('permission_denied');
    }

    public function test_csr_manager_cannot_access_finance(): void
    {
        $this->actingAs($this->createUserWithRole('csr_manager'));
        $this->get('/erp/finance/payments')->assertStatus(302)->assertSessionHas('permission_denied');
    }

    public function test_team_associate_cannot_access_admin(): void
    {
        $this->actingAs($this->createUserWithRole('team_associate'));
        $this->get('/erp/admin/users')->assertStatus(302)->assertSessionHas('permission_denied');
    }

    public function test_team_associate_cannot_access_hr(): void
    {
        $this->actingAs($this->createUserWithRole('team_associate'));
        $this->get('/erp/hr')->assertStatus(302)->assertSessionHas('permission_denied');
    }

    public function test_team_associate_can_read_tasks(): void
    {
        $this->actingAs($this->createUserWithRole('team_associate'));
        $this->get('/erp/tasks')->assertStatus(200);
    }

    public function test_hr_can_access_hr(): void
    {
        $this->actingAs($this->createUserWithRole('hr'));
        $this->get('/erp/hr')->assertStatus(200);
    }

    public function test_finance_can_access_finance(): void
    {
        $this->actingAs($this->createUserWithRole('finance'));
        $this->get('/erp/finance/payments')->assertStatus(200);
    }

    public function test_super_admin_can_access_everything(): void
    {
        $user = $this->createUserWithRole('super_admin');

        $routes = ['/erp/dashboard', '/erp/clients', '/erp/tasks', '/erp/documents', '/erp/finance/payments'];
        foreach ($routes as $route) {
            $this->actingAs($user);
            $this->withoutMiddleware(SessionSecurity::class);
            $this->get($route)->assertStatus(200);
        }
    }
}
