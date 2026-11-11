<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Document;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\InteractsWithRoles;
use Tests\TestCase;

class DocumentSecurityTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    private User $admin;

    private User $associate;

    private Client $assignedClient;

    private Client $unrelatedClient;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        $this->admin = $this->createUserWithRole('super_admin');
        $this->associate = $this->createUserWithRole('team_associate');
        $this->assignedClient = Client::create(['business_name' => 'Assigned', 'status' => 'active', 'created_by' => $this->admin->id]);
        $this->unrelatedClient = Client::create(['business_name' => 'Unrelated', 'status' => 'active', 'created_by' => $this->admin->id]);

        Task::create([
            'title' => 'Tax Return', 'task_number' => 'TSK-DOC-001', 'client_id' => $this->assignedClient->id,
            'status' => 'assigned', 'created_by' => $this->admin->id,
        ])->assignments()->create(['user_id' => $this->associate->id, 'assigned_by' => $this->admin->id]);
    }

    private function createDocument(Client $client, User $uploader): Document
    {
        $path = 'documents/test-'.uniqid().'.pdf';
        Storage::disk('private')->put($path, 'fake pdf content');

        return Document::create([
            'client_id' => $client->id, 'filename' => basename($path), 'original_filename' => 'test.pdf',
            'mime_type' => 'application/pdf', 'size' => 1024, 'storage_path' => $path,
            'storage_disk' => 'private', 'category' => 'Tax', 'uploaded_by' => $uploader->id,
        ]);
    }

    public function test_associate_can_see_assigned_client_documents(): void
    {
        $this->createDocument($this->assignedClient, $this->admin);
        $this->actingAs($this->associate);
        $this->get('/erp/documents')->assertStatus(200)->assertSee('test.pdf');
    }

    public function test_associate_cannot_see_unrelated_client_documents(): void
    {
        $this->createDocument($this->unrelatedClient, $this->admin);
        $this->actingAs($this->associate);
        $this->get('/erp/documents')->assertStatus(200)->assertDontSee('test.pdf');
    }

    public function test_associate_cannot_access_unrelated_client_document(): void
    {
        $doc = $this->createDocument($this->unrelatedClient, $this->admin);
        $this->actingAs($this->associate);
        $this->get("/erp/documents/{$doc->id}")->assertStatus(302)->assertSessionHas('permission_denied');
    }

    public function test_associate_can_access_assigned_client_document(): void
    {
        $doc = $this->createDocument($this->assignedClient, $this->admin);
        $this->actingAs($this->associate);
        $this->get("/erp/documents/{$doc->id}")->assertStatus(200);
    }

    public function test_associate_can_download_assigned_client_document(): void
    {
        $doc = $this->createDocument($this->assignedClient, $this->admin);
        $this->actingAs($this->associate);
        $expires = now()->addMinutes(10)->timestamp;
        $sig = hash_hmac('sha256', "{$doc->id}:{$expires}", config('app.key'));
        $this->get("/erp/documents/{$doc->id}/download?expires={$expires}&sig={$sig}")->assertStatus(200);
    }

    public function test_associate_cannot_download_unrelated_client_document(): void
    {
        $doc = $this->createDocument($this->unrelatedClient, $this->admin);
        $this->actingAs($this->associate);
        $expires = now()->addMinutes(10)->timestamp;
        $sig = hash_hmac('sha256', "{$doc->id}:{$expires}", config('app.key'));
        $this->get("/erp/documents/{$doc->id}/download?expires={$expires}&sig={$sig}")->assertStatus(302)->assertSessionHas('permission_denied');
    }

    public function test_admin_can_see_all_documents(): void
    {
        $this->createDocument($this->assignedClient, $this->admin);
        $this->createDocument($this->unrelatedClient, $this->admin);
        $this->actingAs($this->admin);
        $this->get('/erp/documents')->assertStatus(200)->assertSee('test.pdf');
    }

    public function test_document_access_is_logged(): void
    {
        $doc = $this->createDocument($this->assignedClient, $this->admin);
        $this->actingAs($this->associate);
        $expires = now()->addMinutes(10)->timestamp;
        $sig = hash_hmac('sha256', "{$doc->id}:{$expires}", config('app.key'));
        $this->get("/erp/documents/{$doc->id}/download?expires={$expires}&sig={$sig}");
        $this->assertDatabaseHas('document_access_logs', ['document_id' => $doc->id, 'user_id' => $this->associate->id]);
    }
}
