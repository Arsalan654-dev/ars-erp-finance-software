<?php

namespace Tests\Feature;

use App\Http\Middleware\SessionSecurity;
use App\Models\Client;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\InteractsWithRoles;
use Tests\TestCase;

class DocumentUploadTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    private User $admin;

    private User $associate;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        $this->admin = $this->createUserWithRole('super_admin');
        $this->associate = $this->createUserWithRole('team_associate');
        $this->client = Client::create(['business_name' => 'Doc Client', 'status' => 'active', 'created_by' => $this->admin->id]);
    }

    private function asAdmin()
    {
        $this->actingAs($this->admin);
        $this->withoutMiddleware(SessionSecurity::class);

        return $this;
    }

    public function test_upload_document(): void
    {
        $this->asAdmin();
        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        $response = $this->post('/erp/documents', [
            'client_id' => $this->client->id,
            'document_file' => $file,
            'category' => 'Tax',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('documents', [
            'client_id' => $this->client->id,
            'original_filename' => 'test.pdf',
        ]);
    }

    public function test_team_associate_cannot_upload(): void
    {
        $this->actingAs($this->associate);
        $this->withoutMiddleware(SessionSecurity::class);
        $this->get('/erp/documents/create')->assertStatus(302)->assertSessionHas('permission_denied');
    }

    public function test_upload_requires_file(): void
    {
        $this->asAdmin();
        $response = $this->post('/erp/documents', [
            'client_id' => $this->client->id,
            'category' => 'Tax',
        ]);

        $response->assertSessionHasErrors('document_file');
    }

    public function test_delete_own_document(): void
    {
        $doc = Document::create([
            'client_id' => $this->client->id,
            'filename' => 'test.pdf',
            'original_filename' => 'test.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'storage_path' => 'documents/test.pdf',
            'storage_disk' => 'private',
            'category' => 'Tax',
            'uploaded_by' => $this->admin->id,
        ]);

        Storage::disk('private')->put('documents/test.pdf', 'fake content');

        $this->asAdmin();
        $this->delete("/erp/documents/{$doc->id}")->assertRedirect();
        $this->assertSoftDeleted('documents', ['id' => $doc->id]);
    }

    public function test_team_associate_cannot_delete_others_document(): void
    {
        $otherAdmin = $this->createUserWithRole('super_admin');
        $doc = Document::create([
            'client_id' => $this->client->id,
            'filename' => 'test.pdf',
            'original_filename' => 'test.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'storage_path' => 'documents/test.pdf',
            'storage_disk' => 'private',
            'category' => 'Tax',
            'uploaded_by' => $otherAdmin->id,
        ]);

        $this->actingAs($this->associate);
        $this->withoutMiddleware(SessionSecurity::class);
        $this->delete("/erp/documents/{$doc->id}")->assertStatus(302)->assertSessionHas('permission_denied');
    }

    public function test_admin_can_delete_any_document(): void
    {
        $otherAdmin = $this->createUserWithRole('super_admin');
        $doc = Document::create([
            'client_id' => $this->client->id,
            'filename' => 'test.pdf',
            'original_filename' => 'test.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'storage_path' => 'documents/test.pdf',
            'storage_disk' => 'private',
            'category' => 'Tax',
            'uploaded_by' => $otherAdmin->id,
        ]);

        Storage::disk('private')->put('documents/test.pdf', 'fake content');

        $this->asAdmin();
        $this->delete("/erp/documents/{$doc->id}")->assertRedirect();
        $this->assertSoftDeleted('documents', ['id' => $doc->id]);
    }

    public function test_access_history_for_document(): void
    {
        $doc = Document::create([
            'client_id' => $this->client->id,
            'filename' => 'test.pdf',
            'original_filename' => 'test.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'storage_path' => 'documents/test.pdf',
            'storage_disk' => 'private',
            'category' => 'Tax',
            'uploaded_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin);
        $this->get("/erp/documents/{$doc->id}/access-history")->assertStatus(200);
    }
}
