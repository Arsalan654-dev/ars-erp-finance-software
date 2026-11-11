<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientContact;
use App\Models\ClientService;
use App\Services\AuditService;
use App\Services\CsrAssigner;
use App\Services\SensitiveDataService;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    /**
     * Client list with fast search (phone, email, business name, EIN, UTR).
     */
    public function index(Request $request)
    {
        $query = Client::query();

        $search = $request->get('q');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('client_number', 'like', "%{$search}%")
                    ->orWhere('business_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('ein_hash', SensitiveDataService::hash($search))
                    ->orWhere('utr_hash', SensitiveDataService::hash($search))
                    ->orWhere('itin_hash', SensitiveDataService::hash($search));
            });
        }

        if ($request->has('status') && $request->get('status')) {
            $query->where('status', $request->get('status'));
        }

        // Drill-down filters used by the pipeline stat cards (Point 3).
        if ($request->filled('payment')) {
            $payment = $request->get('payment');
            $clientIds = Client::query()
                ->whereHas('invoices', function ($q) use ($payment): void {
                    if ($payment === 'partial') {
                        $q->where('paid_amount', '>', 0)->where('balance', '>', 0);
                    } elseif ($payment === 'paid') {
                        $q->where('balance', '<=', 0)->where('total', '>', 0);
                    }
                })
                ->pluck('id');
            $query->whereIn('id', $clientIds);
        }

        if ($request->filled('with')) {
            $with = $request->get('with');
            if ($with === 'quotation') {
                $query->whereHas('quotations');
            } elseif ($with === 'invoice') {
                $query->whereHas('invoices');
            }
        }

        if ($request->filled('lost')) {
            $query->whereHas('leads', fn ($l) => $l->where('status', 'lost'));
        }

        $clients = $query->withCount(['invoices', 'tasks', 'documents'])->latest()->paginate(15)->withQueryString();

        return view('erp.crm.index', compact('clients'));
    }

    public function create()
    {
        return view('erp.crm.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'country' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'ein' => ['nullable', 'string', 'max:50'],
            'itin' => ['nullable', 'string', 'max:50'],
            'utr' => ['nullable', 'string', 'max:50'],
            'ssn' => ['nullable', 'string', 'max:50'],
            'tax_type' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:active,inactive,lead'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['created_by'] = auth()->id();

        $client = Client::create($data);

        // Every new client is divided equally across the CSR team (Point 11).
        $assigned = CsrAssigner::assignClient($client);

        AuditService::log('CLIENT_CREATE', 'client', $client->id, [
            'business_name' => $client->business_name,
            'assigned_to' => $client->assigned_to,
        ]);

        $message = $assigned
            ? 'Client created successfully. Assigned to '.$assigned->name.'.'
            : 'Client created successfully.';

        return redirect()->route('erp.clients.show', $client)->with('success', $message);
    }

    public function show(Client $client)
    {
        $client->load(['clientNotes.user', 'history.user']);

        $contacts = $client->contacts()->latest()->paginate(10, ['*'], 'contacts_page')->withQueryString();
        $services = $client->services()->latest()->paginate(10, ['*'], 'services_page')->withQueryString();
        $quotations = $client->quotations()->latest()->paginate(10, ['*'], 'quotations_page')->withQueryString();
        $invoices = $client->invoices()->latest()->paginate(10, ['*'], 'invoices_page')->withQueryString();
        $payments = $client->payments()->latest()->paginate(10, ['*'], 'payments_page')->withQueryString();
        $tasks = $client->tasks()->latest()->paginate(10, ['*'], 'tasks_page')->withQueryString();
        $documents = $client->documents()->latest()->paginate(10, ['*'], 'documents_page')->withQueryString();
        $complianceRecords = $client->complianceRecords()->latest()->paginate(10, ['*'], 'compliance_page')->withQueryString();

        return view('erp.crm.show', compact(
            'client', 'contacts', 'services', 'quotations', 'invoices',
            'payments', 'tasks', 'documents', 'complianceRecords'
        ));
    }

    public function edit(Client $client)
    {
        return view('erp.crm.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'country' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'ein' => ['nullable', 'string', 'max:50'],
            'itin' => ['nullable', 'string', 'max:50'],
            'utr' => ['nullable', 'string', 'max:50'],
            'ssn' => ['nullable', 'string', 'max:50'],
            'tax_type' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:active,inactive,lead'],
            'notes' => ['nullable', 'string'],
        ]);

        $client->update($data);

        AuditService::log('CLIENT_UPDATE', 'client', $client->id, ['business_name' => $client->business_name]);

        return redirect()->route('erp.clients.show', $client)->with('success', 'Client updated successfully.');
    }

    public function destroy(Client $client)
    {
        AuditService::log('CLIENT_DELETE', 'client', $client->id, ['business_name' => $client->business_name]);

        $client->delete();

        return redirect()->route('erp.clients.index')->with('success', 'Client deleted.');
    }

    // ---- Nested resources ----

    public function addContact(Request $request, Client $client)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string'],
            'position' => ['nullable', 'string'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('is_primary')) {
            $client->contacts()->update(['is_primary' => false]);
        }

        $client->contacts()->create($data);

        AuditService::log('CONTACT_ADD', 'client', $client->id);

        return back()->with('success', 'Contact added.');
    }

    public function deleteContact(Client $client, ClientContact $contact)
    {
        if ($contact->client_id !== $client->id) {
            abort(404);
        }

        $contact->delete();

        return back()->with('success', 'Contact removed.');
    }

    public function addService(Request $request, Client $client)
    {
        $data = $request->validate([
            'service_name' => ['required', 'string'],
            'status' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $client->services()->create($data);

        AuditService::log('SERVICE_ADD', 'client', $client->id, ['service' => $data['service_name']]);

        return back()->with('success', 'Service added.');
    }

    public function deleteService(Client $client, ClientService $service)
    {
        if ($service->client_id !== $client->id) {
            abort(404);
        }

        $service->delete();

        return back()->with('success', 'Service removed.');
    }

    public function addNote(Request $request, Client $client)
    {
        $data = $request->validate([
            'content' => ['required', 'string'],
            'type' => ['nullable', 'string', 'in:general,call,email,meeting'],
        ]);

        $client->clientNotes()->create([
            'user_id' => auth()->id(),
            'content' => $data['content'],
            'type' => $data['type'] ?? 'general',
        ]);

        return back()->with('success', 'Note added.');
    }
}
