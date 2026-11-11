<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use App\Models\EmailQueue;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;

class EmailController extends Controller
{
    public function templates()
    {
        $templates = EmailTemplate::latest()->paginate(12);

        return view('erp.email.templates.index', compact('templates'));
    }

    public function storeTemplate(Request $request)
    {
        $data = $request->validate([
            'slug' => ['required', 'string', 'unique:email_templates,slug'],
            'name' => ['required', 'string'],
            'subject' => ['required', 'string'],
            'body' => ['required', 'string'],
            'variables' => ['nullable', 'json'],
            'language' => ['nullable', 'string'],
        ]);

        EmailTemplate::create($data);

        return back()->with('success', 'Email template created.');
    }

    public function updateTemplate(Request $request, EmailTemplate $template)
    {
        $data = $request->validate([
            'name' => ['required', 'string'],
            'subject' => ['required', 'string'],
            'body' => ['required', 'string'],
            'variables' => ['nullable', 'json'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $template->update($data);

        return back()->with('success', 'Template updated.');
    }

    public function logs(Request $request)
    {
        $query = EmailLog::query();

        if ($request->get('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->get('q')) {
            $query->where('recipient_email', 'like', '%'.$request->get('q').'%');
        }

        $logs = $query->latest()->paginate(20)->withQueryString();

        return view('erp.email.logs', compact('logs'));
    }

    public function queue()
    {
        $queue = EmailQueue::latest()->paginate(20);

        return view('erp.email.queue', compact('queue'));
    }

    public function processQueue()
    {
        $pending = EmailQueue::where('status', 'queued')->where(function ($q) {
            $q->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now());
        })->limit(10)->get();

        $processed = 0;
        foreach ($pending as $item) {
            \App\Services\EmailService::process($item);
            $processed++;
        }

        return back()->with('success', "Processed {$processed} emails from queue.");
    }
}