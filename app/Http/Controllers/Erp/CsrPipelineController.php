<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\User;
use App\Services\PipelineStats;

class CsrPipelineController extends Controller
{
    public function index()
    {
        $counts = PipelineStats::counts();

        // ---- Per-CSR breakdown ----
        $leadStats = Lead::selectRaw('assigned_to, COUNT(*) as total')
            ->whereNotNull('assigned_to')
            ->groupBy('assigned_to')
            ->pluck('total', 'assigned_to');

        $statusByCsr = Lead::selectRaw('assigned_to, status, COUNT(*) as c')
            ->whereNotNull('assigned_to')
            ->groupBy(['assigned_to', 'status'])
            ->get()
            ->groupBy('assigned_to');

        $invoiceByCsr = Lead::selectRaw('assigned_to, COUNT(DISTINCT client_id) as c')
            ->whereNotNull('assigned_to')
            ->whereNotNull('client_id')
            ->whereHas('client.invoices')
            ->groupBy('assigned_to')
            ->pluck('c', 'assigned_to');

        $clientByCsr = Lead::selectRaw('assigned_to, COUNT(DISTINCT client_id) as c')
            ->whereNotNull('assigned_to')
            ->whereNotNull('client_id')
            ->groupBy('assigned_to')
            ->pluck('c', 'assigned_to');

        $csrs = User::whereHas('roles', function ($q): void {
            $q->whereIn('name', ['csr_manager', 'team_associate']);
        })->orderBy('name')->get();

        $rows = $csrs->map(function (User $user) use ($leadStats, $statusByCsr, $invoiceByCsr, $clientByCsr) {
            $statusRow = $statusByCsr->get($user->id);

            $countFor = function (string $status) use ($statusRow): int {
                $match = $statusRow?->firstWhere('status', $status);

                return (int) ($match->c ?? 0);
            };

            return [
                'user' => $user,
                'total' => (int) ($leadStats[$user->id] ?? 0),
                'clients' => (int) ($clientByCsr[$user->id] ?? 0),
                'followUp' => $countFor('follow_up'),
                'quotation' => $countFor('quotation'),
                'converted' => $countFor('converted'),
                'convertedWithInvoice' => (int) ($invoiceByCsr[$user->id] ?? 0),
                'lost' => $countFor('lost'),
            ];
        });

        return view('erp.pipeline.index', [
            'counts' => $counts,
            'rows' => $rows,
        ]);
    }
}
