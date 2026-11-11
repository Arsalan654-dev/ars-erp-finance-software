<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\WhatsappAccount;
use Illuminate\Http\Request;

class WhatsappAccountController extends Controller
{
    public function index()
    {
        $accounts = WhatsappAccount::latest()->paginate(15);
        return view('erp.admin.whatsapp.index', compact('accounts'));
    }

    public function create()
    {
        return view('erp.admin.whatsapp.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'provider' => ['required', 'in:meta_cloud,evolution'],
            'phone_number_id' => ['required', 'string', 'max:255'],
            'api_url' => ['nullable', 'url'],
            'api_token' => ['required', 'string', 'max:500'],
            'verify_token' => ['nullable', 'string', 'max:255'],
        ]);

        $data['api_token'] = encrypt($data['api_token']);
        $data['is_active'] = true;

        WhatsappAccount::create($data);

        return redirect()->route('erp.admin.whatsapp.index')->with('success', 'WhatsApp account created.');
    }

    public function edit(WhatsappAccount $account)
    {
        return view('erp.admin.whatsapp.edit', compact('account'));
    }

    public function update(Request $request, WhatsappAccount $account)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'provider' => ['required', 'in:meta_cloud,evolution'],
            'phone_number_id' => ['required', 'string', 'max:255'],
            'api_url' => ['nullable', 'url'],
            'api_token' => ['nullable', 'string', 'max:500'],
            'verify_token' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        if (! empty($data['api_token'])) {
            $data['api_token'] = encrypt($data['api_token']);
        } else {
            unset($data['api_token']);
        }

        if (isset($data['verify_token']) && ! empty($data['verify_token'])) {
            $data['verify_token'] = encrypt($data['verify_token']);
        } else {
            unset($data['verify_token']);
        }

        $data['is_active'] = $request->boolean('is_active');

        $account->update($data);

        return redirect()->route('erp.admin.whatsapp.index')->with('success', 'WhatsApp account updated.');
    }

    public function destroy(WhatsappAccount $account)
    {
        $account->delete();
        return back()->with('success', 'WhatsApp account deleted.');
    }
}
