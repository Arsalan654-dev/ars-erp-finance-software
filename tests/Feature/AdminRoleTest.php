<?php

namespace Tests\Feature;

use App\Http\Middleware\SessionSecurity;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\InteractsWithRoles;
use Tests\TestCase;

class AdminRoleTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    private User $superAdmin;

    private User $hrUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->superAdmin = $this->createUserWithRole('super_admin');
        $this->hrUser = $this->createUserWithRole('hr');
    }

    private function asAdmin()
    {
        $this->actingAs($this->superAdmin);
        $this->withoutMiddleware(SessionSecurity::class);

        return $this;
    }

    public function test_super_admin_can_view_users(): void
    {
        $this->actingAs($this->superAdmin);
        $this->get('/erp/admin/users')->assertStatus(200);
    }

    public function test_hr_cannot_view_admin_users(): void
    {
        $this->actingAs($this->hrUser);
        $this->withoutMiddleware(SessionSecurity::class);
        $this->get('/erp/admin/users')->assertStatus(302)->assertSessionHas('permission_denied');
    }

    public function test_create_user(): void
    {
        $this->asAdmin();
        $response = $this->post('/erp/admin/users', [
            'name' => 'New User',
            'email' => 'new@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'new@test.com']);
    }

    public function test_create_user_with_role(): void
    {
        $financeRole = Role::where('name', 'finance')->first();

        $this->asAdmin();
        $this->post('/erp/admin/users', [
            'name' => 'Finance User',
            'email' => 'fin@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => [$financeRole->id],
        ]);

        $user = User::where('email', 'fin@test.com')->first();
        $this->assertTrue($user->hasRole('finance'));
    }

    public function test_duplicate_email_rejected(): void
    {
        $this->asAdmin();
        $response = $this->post('/erp/admin/users', [
            'name' => 'Duplicate',
            'email' => $this->superAdmin->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_create_role(): void
    {
        $this->asAdmin();
        $permIds = Permission::pluck('id')->take(3)->toArray();

        $this->post('/erp/admin/roles', [
            'name' => 'custom_role_'.uniqid(),
            'permissions' => $permIds,
        ]);

        $role = Role::where('name', 'like', 'custom_role_%')->first();
        $this->assertNotNull($role);
        $this->assertEquals(3, $role->permissions->count());
    }

    public function test_update_role_permissions(): void
    {
        $role = Role::create(['name' => 'temp_role']);
        $perm = Permission::firstOrCreate(
            ['slug' => 'client.read'],
            ['name' => 'Client Read', 'group' => 'client']
        );
        $role->permissions()->sync([$perm->id]);

        $newPerm = Permission::firstOrCreate(
            ['slug' => 'client.create'],
            ['name' => 'Client Create', 'group' => 'client']
        );

        $this->asAdmin();
        $this->patch("/erp/admin/roles/{$role->id}", [
            'name' => 'temp_role',
            'permissions' => [$perm->id, $newPerm->id],
        ]);

        $this->assertEquals(2, $role->fresh()->permissions->count());
    }

    public function test_cannot_deactivate_last_super_admin(): void
    {
        $this->asAdmin();
        $this->patch("/erp/admin/users/{$this->superAdmin->id}", [
            'name' => $this->superAdmin->name,
            'email' => $this->superAdmin->email,
            'is_active' => false,
        ])->assertSessionHas('error');

        $this->assertTrue($this->superAdmin->fresh()->is_active);
    }

    public function test_can_deactivate_non_last_super_admin(): void
    {
        $otherAdmin = $this->createUserWithRole('super_admin');

        $this->asAdmin();
        $this->patch("/erp/admin/users/{$this->superAdmin->id}", [
            'name' => $this->superAdmin->name,
            'email' => $this->superAdmin->email,
            'is_active' => false,
        ]);

        $this->assertFalse($this->superAdmin->fresh()->is_active);
    }

    public function test_cannot_remove_last_super_admin_role(): void
    {
        $financeRoleId = Role::where('name', 'finance')->first()->id;

        $this->asAdmin();
        $this->patch("/erp/admin/users/{$this->superAdmin->id}", [
            'name' => $this->superAdmin->name,
            'email' => $this->superAdmin->email,
            'roles' => [$financeRoleId],
        ])->assertSessionHas('error');

        $this->assertTrue($this->superAdmin->fresh()->hasRole('super_admin'));
    }

    public function test_view_audit_logs(): void
    {
        $this->actingAs($this->superAdmin);
        $this->get('/erp/admin/audit-logs')->assertStatus(200);
    }

    public function test_audit_logs_filter_by_action(): void
    {
        $this->actingAs($this->superAdmin);
        $this->get('/erp/admin/audit-logs?action=LOGIN')->assertStatus(200);
    }

    public function test_view_settings(): void
    {
        $this->actingAs($this->superAdmin);
        $this->get('/erp/admin/settings')->assertStatus(200);
    }

    public function test_update_settings(): void
    {
        $this->asAdmin();
        $response = $this->post('/erp/admin/settings', [
            'company_name' => 'Test Company',
            'company_email' => 'company@test.com',
            'default_currency' => 'USD',
        ]);

        $response->assertSessionHas('success');
    }

    public function test_settings_rejects_invalid_currency(): void
    {
        $this->asAdmin();
        $response = $this->post('/erp/admin/settings', [
            'default_currency' => 'INVALID',
        ]);

        $response->assertSessionHasErrors('default_currency');
    }
}
