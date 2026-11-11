<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentAccessLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Private document vault: files live outside the public web root,
 * access is permission-checked, every view/download is audited,
 * and downloads use short-lived signed URLs.
 */
class DocumentService
{
    public const SIGNED_URL_TTL_MINUTES = 10;

    /**
     * Store an uploaded file privately and create a Document record.
     */
    public static function store(
        UploadedFile $file,
        ?int $clientId = null,
        ?int $complianceRecordId = null,
        ?string $category = null,
        ?string $description = null
    ): Document {
        $relativePath = 'documents/'.($clientId ?: 'general').'/'.Str::uuid().'.'.$file->extension();
        $storedPath = Storage::disk('private')->putFileAs(
            dirname($relativePath),
            $file,
            basename($relativePath)
        );

        $doc = Document::create([
            'client_id' => $clientId,
            'compliance_record_id' => $complianceRecordId,
            'filename' => basename($storedPath),
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'storage_path' => $storedPath,
            'storage_disk' => 'private',
            'category' => $category,
            'description' => $description,
            'checksum' => hash_file('sha256', $file->getRealPath()),
            'uploaded_by' => auth()->id(),
        ]);

        AuditService::log('DOCUMENT_UPLOAD', 'document', $doc->id, [
            'filename' => $doc->original_filename,
            'client_id' => $clientId,
        ]);

        return $doc;
    }

    /**
     * Verify the user is allowed to access a document.
     *
     * Access rules:
     *  - Super Admin / CSR Manager / Finance / HR → full access
     *  - Document uploader → always allowed
     *  - Team Associate → allowed only if they have an active task
     *    assigned to them on the document's client (or document has no client)
     */
    public static function canAccess(?int $userId, Document $doc): bool
    {
        $user = $userId ? \App\Models\User::find($userId) : auth()->user();
        if (! $user) {
            return false;
        }

        // Full-access roles
        if ($user->isSuperAdmin() || $user->hasAnyRole(['csr_manager', 'finance', 'hr'])) {
            return true;
        }

        // Uploader always has access to their own uploads
        if ($doc->uploaded_by === $user->id) {
            return true;
        }

        // Team Associate: must have an active task on the document's client
        if ($user->hasRole('team_associate')) {
            // General documents (no client): only accessible to HR/Admin/uploader
            if (! $doc->client_id) {
                return $doc->uploaded_by === $user->id;
            }

            return \App\Models\Task::where('client_id', $doc->client_id)
                ->whereHas('assignments', function ($q) use ($user) {
                    $q->where('user_id', $user->id)->whereNull('unassigned_at');
                })
                ->exists();
        }

        return false;
    }

    /**
     * Generate a short-lived signed download URL.
     */
    public static function signedDownloadUrl(Document $doc): string
    {
        $expires = now()->addMinutes(self::SIGNED_URL_TTL_MINUTES)->timestamp;
        $signature = self::signature($doc->id, $expires);

        return route('erp.documents.download', [
            'document' => $doc->id,
            'expires' => $expires,
            'sig' => $signature,
        ]);
    }

    /**
     * Validate a signed URL.
     */
    public static function validateSignature(int $documentId, int $expires, string $signature): bool
    {
        if ($expires < now()->timestamp) {
            return false;
        }

        return hash_equals(self::signature($documentId, $expires), $signature);
    }

    private static function signature(int $documentId, int $expires): string
    {
        return hash_hmac('sha256', "{$documentId}:{$expires}", config('app.key'));
    }

    /**
     * Stream a document to the browser (records access log + audit trail).
     */
    public static function stream(Document $doc): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        DocumentAccessLog::create([
            'document_id' => $doc->id,
            'user_id' => auth()->id(),
            'action' => 'download',
            'ip_address' => request()->ip(),
            'user_agent' => substr(request()->userAgent() ?? '', 0, 500),
        ]);

        AuditService::log('DOCUMENT_DOWNLOAD', 'document', $doc->id, [
            'filename' => $doc->original_filename,
        ]);

        return Storage::disk($doc->storage_disk)->download($doc->storage_path, $doc->original_filename);
    }
}