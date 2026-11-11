<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Services\SensitiveDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\InteractsWithRoles;

class ClientEncryptionTest extends TestCase
{
    use RefreshDatabase, InteractsWithRoles;

    private $admin;
    private $associate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->createUserWithRole('super_admin');
        $this->associate = $this->createUserWithRole('team_associate');
    }

    public function test_ein_is_encrypted_in_database(): void
    {
        $client = Client::create(['business_name' => 'Test Corp', 'status' => 'active', 'created_by' => $this->admin->id]);
        $client->ein = '12-3456789';
        $client->save();

        $raw = \DB::table('clients')->where('id', $client->id)->first();
        $this->assertNotNull($raw->ein_encrypted);
        $this->assertNotEquals('12-3456789', $raw->ein_encrypted);
        $this->assertEquals(hash('sha256', '12-3456789'), $raw->ein_hash);
    }

    public function test_ssn_is_encrypted_in_database(): void
    {
        $client = Client::create(['business_name' => 'Test Corp 2', 'status' => 'active', 'created_by' => $this->admin->id]);
        $client->ssn = '123-45-6789';
        $client->save();

        $raw = \DB::table('clients')->where('id', $client->id)->first();
        $this->assertNotNull($raw->ssn_encrypted);
        $this->assertNotEquals('123-45-6789', $raw->ssn_encrypted);
        $this->assertEquals(hash('sha256', '123-45-6789'), $raw->ssn_hash);
    }

    public function test_encrypted_fields_decrypt_on_read(): void
    {
        $client = Client::create(['business_name' => 'Test Corp 3', 'status' => 'active', 'created_by' => $this->admin->id]);
        $client->ein = '98-7654321';
        $client->utr = 'AB123456789';
        $client->save();

        $fresh = $client->fresh();
        $this->assertEquals('98-7654321', $fresh->ein);
        $this->assertEquals('AB123456789', $fresh->utr);
    }

    public function test_client_search_via_hash(): void
    {
        $client = Client::create(['business_name' => 'Hash Search Corp', 'status' => 'active', 'created_by' => $this->admin->id]);
        $client->ein = '55-1234567';
        $client->save();

        $this->actingAs($this->admin);
        $this->get('/erp/clients?search=55-1234567')->assertStatus(200)->assertSee('Hash Search Corp');
    }

    public function test_masked_ein_for_non_admin(): void
    {
        $client = Client::create(['business_name' => 'Mask Test', 'status' => 'active', 'created_by' => $this->admin->id]);
        $client->ein = '12-3456789';
        $client->save();

        $this->actingAs($this->associate);
        $response = $this->get("/erp/clients/{$client->id}");
        $response->assertStatus(200);
        $response->assertDontSee('12-3456789');
        $response->assertSee('XXXXX6789');
    }

    public function test_can_view_sensitive_only_for_admin(): void
    {
        $this->actingAs($this->admin);
        $this->assertTrue(SensitiveDataService::canViewSensitive());

        $this->actingAs($this->associate);
        $this->assertFalse(SensitiveDataService::canViewSensitive());
    }

    public function test_null_encrypted_fields_handled(): void
    {
        $client = Client::create(['business_name' => 'Null Test', 'status' => 'active', 'created_by' => $this->admin->id]);
        $this->assertNull($client->ein);
        $this->assertNull($client->ssn);
        $this->assertNull($client->utr);
        $this->assertNull($client->itin);
    }
}
