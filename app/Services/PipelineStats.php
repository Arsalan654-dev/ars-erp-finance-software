<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;

/**
 * CSR Pipeline counters (Leads + Clients mixed) used by both
 * the dashboard widget and the dedicated CSR Pipeline page.
 *
 * Lead stages count leads; paid/outstanding counters count DISTINCT CLIENTS,
 * so duplicate invoices never inflate the pipeline.
 */
class PipelineStats
{
    public static function counts(): array
    {
        $statusCounts = Lead::selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $convertedWithInvoice = Lead::where('status', 'converted')
            ->whereNotNull('client_id')
            ->whereHas('client.invoices')
            ->count();

        // Client-based paid counters: one row per client, not per invoice.
        $byClient = DB::table('invoices')
            ->whereNotNull('client_id')
            ->groupBy('client_id')
            ->selectRaw('
                client_id,
                MAX(CASE WHEN balance > 0 AND paid_amount > 0 THEN 1 ELSE 0 END) as has_partial,
                MAX(CASE WHEN balance <= 0 AND paid_amount > 0 THEN 1 ELSE 0 END) as has_paid,
                SUM(CASE WHEN balance > 0 THEN 1 ELSE 0 END) as outstanding
            ')
            ->get();

        $partiallyPaid = $byClient
            ->filter(fn ($row) => (int) $row->has_partial === 1 && (int) $row->outstanding > 0)
            ->count();

        $fullyPaid = $byClient
            ->filter(fn ($row) => (int) $row->has_paid === 1 && (int) $row->outstanding === 0)
            ->count();

        return [
            'total' => (int) Lead::count(),
            'clients' => (int) Client::count(),
            'followUp' => (int) ($statusCounts['follow_up'] ?? 0),
            'quotation' => (int) ($statusCounts['quotation'] ?? 0),
            'converted' => (int) ($statusCounts['converted'] ?? 0),
            'convertedWithInvoice' => $convertedWithInvoice,
            'lost' => (int) ($statusCounts['lost'] ?? 0),
            'partiallyPaid' => $partiallyPaid,
            'fullyPaid' => $fullyPaid,
        ];
    }
}
