<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\InteractsWithRoles;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    private function actingAsRole(string $role): User
    {
        $user = $this->createUserWithRole($role);
        $this->actingAs($user);

        return $user;
    }

    public function test_hr_cannot_access_finance(): void
    {
        $user = $this->actingAsRole('hr');
        $this->get('/erp/finance/payments')->assertStatus(302)->assertSessionHas('permission_denied');
    }

    public function test_hr_cannot_access_admin(): void
    {
        $user = $this->actingAsRole('hr');
        $this->get('/erp/admin/users')->assertStatus(302)->assertSessionHas('permission_denied');
    }

    public function test_finance_cannot_access_hr(): void
    {
        $user = $this->actingAsRole('finance');
        $this->get('/erp/hr')->assertStatus(302)->assertSessionHas('permission_denied');
    }

    public function test_finance_cannot_access_admin(): void
    {
        $user = $this->actingAsRole('finance');
        $this->get('/erp/admin/users')->assertStatus(302)->assertSessionHas('permission_denied');
    }

    public function test_csr_manager_cannot_access_finance(): void
    {
        $user = $this->actingAsRole('csr_manager');
        $this->get('/erp/finance/payments')->assertStatus(302)->assertSessionHas('permission_denied');
    }

    public function test_team_associate_cannot_access_finance(): void
    {
        $user = $this->actingAsRole('team_associate');
        $this->get('/erp/finance/payments')->assertStatus(302)->assertSessionHas('permission_denied');
    }

    public function test_team_associate_cannot_access_hr(): void
    {
        $user = $this->actingAsRole('team_associate');
        $this->get('/erp/hr')->assertStatus(302)->assertSessionHas('permission_denied');
    }

    public function test_team_associate_cannot_access_admin(): void
    {
        $user = $this->actingAsRole('team_associate');
        $this->get('/erp/admin/users')->assertStatus(302)->assertSessionHas('permission_denied');
    }

    public function test_super_admin_can_access_all(): void
    {
        $routes = [
            '/erp/dashboard',
            '/erp/clients',
            '/erp/finance/payments',
            '/erp/hr',
            '/erp/admin/users',
            '/erp/tasks',
            '/erp/documents',
        ];

        foreach ($routes as $route) {
            $user = $this->actingAsRole('super_admin');
            $this->get($route)->assertStatus(200);
        }
    }

    public function test_unauthenticated_redirected_to_login(): void
    {
        auth()->logout();
        $this->get('/erp/dashboard')->assertStatus(302);
    }

    public function test_sensitive_data_masked_for_non_admin(): void
    {
        $admin = $this->createUserWithRole('super_admin');
        $client = Client::create([
            'business_name' => 'Test Corp',
            'status' => 'active',
            'created_by' => $admin->id,
        ]);
        $client->ein = '12-3456789';
        $client->save();

        $raw = \DB::table('clients')->where('id', $client->id)->first();
        $this->assertNotNull($raw->ein_encrypted);

        $associate = $this->createUserWithRole('team_associate');
        $this->actingAs($associate);
        $response = $this->get("/erp/clients/{$client->id}");
        $response->assertStatus(200);
        $response->assertDontSee('12-3456789');
        $response->assertSee('XXXXX6789');
    }

    public function test_sensitive_data_visible_for_super_admin(): void
    {
        $admin = $this->createUserWithRole('super_admin');
        $client = Client::create([
            'business_name' => 'Test Corp',
            'status' => 'active',
            'created_by' => $admin->id,
        ]);
        $client->ein = '12-3456789';
        $client->save();

        $this->actingAs($admin);
        $response = $this->get("/erp/clients/{$client->id}");
        $response->assertStatus(200);
        $response->assertSee('12-3456789');
    }
}
