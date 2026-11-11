<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\AuditService;
use App\Services\DocumentService;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Document::with('client');

        // Team Associate: only show documents for their assigned clients
        if ($user->hasRole('team_associate')) {
            $assignedClientIds = \App\Models\Task::whereHas('assignments', function ($q) use ($user) {
                $q->where('user_id', $user->id)->whereNull('unassigned_at');
            })->pluck('client_id')->filter()->unique()->values()->toArray();

            $query->where(function ($q) use ($assignedClientIds, $user) {
                $q->whereIn('client_id', $assignedClientIds)
                    // General docs: only show ones they uploaded
                    ->orWhere(function ($q2) use ($user) {
                        $q2->whereNull('client_id')->where('uploaded_by', $user->id);
                    });
            });
        }

        if ($request->get('category')) {
            $query->where('category', $request->get('category'));
        }
        if ($request->get('q')) {
            $query->where('original_filename', 'like', '%'.$request->get('q').'%');
        }

        $documents = $query->latest()->paginate(15)->withQueryString();

        return view('erp.documents.index', compact('documents'));
    }

    public function create()
    {
        $clients = \App\Models\Client::where('status', 'active')->orderBy('business_name')->get();

        return view('erp.documents.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => ['nullable', 'exists:clients,id'],
            'document_file' => ['required', 'file', 'max:51200'],
            'category' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ]);

        $document = DocumentService::store(
            $request->file('document_file'),
            $data['client_id'] ?? null,
            null,
            $data['category'] ?? null,
            $data['description'] ?? null
        );

        return redirect()->route('erp.documents.show', $document)->with('success', 'Document uploaded to private vault.');
    }

    public function show(Document $document)
    {
        if (! DocumentService::canAccess(auth()->id(), $document)) {
            abort(403, 'You do not have permission to access this document.');
        }

        $document->load(['client', 'versions', 'accessLogs.user', 'uploader', 'complianceRecord']);

        return view('erp.documents.show', compact('document'));
    }

    /**
     * Short-lived signed URL download. Access checked, audit logged.
     */
    public function download(Request $request, Document $document)
    {
        $expires = (int) $request->get('expires');
        $sig = (string) $request->get('sig');

        if (! DocumentService::validateSignature($document->id, $expires, $sig)) {
            abort(403, 'This download link is invalid or has expired.');
        }

        if (! DocumentService::canAccess(auth()->id(), $document)) {
            abort(403, 'You do not have permission to access this document.');
        }

        return DocumentService::stream($document);
    }

    public function accessHistory(Document $document)
    {
        if (! DocumentService::canAccess(auth()->id(), $document)) {
            abort(403, 'You do not have permission to access this document.');
        }

        return response()->json($document->accessLogs()->with('user')->latest()->get());
    }

    public function destroy(Document $document)
    {
        if (! auth()->user()->hasRole('super_admin') && $document->uploaded_by !== auth()->id()) {
            abort(403, 'Only the uploader or a super admin can delete documents.');
        }

        AuditService::log('DOCUMENT_DELETE', 'document', $document->id);

        \Illuminate\Support\Facades\Storage::disk($document->storage_disk)->delete($document->storage_path);
        $document->delete();

        return redirect()->route('erp.documents.index')->with('success', 'Document deleted.');
    }
}