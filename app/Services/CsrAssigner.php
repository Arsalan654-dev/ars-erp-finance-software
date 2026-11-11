<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Lead;
use App\Models\User;

/**
 * Portfolio-aware CSR assignment for incoming leads.
 *
 * 1. A repeat lead is matched against the existing client portfolio
 *    (email / phone / business name) so no duplicate client is created.
 * 2. A matched client keeps its previous CSR.
 * 3. When no CSR is known the lead is handed to the least-loaded active CSR
 *    (ties broken randomly) so the workload divides equally over time.
 */
class CsrAssigner
{
    /**
     * Find an existing portfolio client for the given lead attributes.
     */
    public static function findClient(?string $email, ?string $phone, ?string $businessName): ?Client
    {
        if ($email) {
            $client = Client::where('email', $email)->first();
            if ($client) {
                return $client;
            }
        }

        if ($phone) {
            $digits = preg_replace('/\D+/', '', $phone);
            if ($digits !== '') {
                // Compare digit-for-digit: either side may be stored formatted
                // (+44 20 1234 5678) or raw (442012345678).
                $client = Client::whereRaw(
                    "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), '.', ''), '(', ''), ')', '') LIKE ?",
                    ['%'.$digits.'%']
                )->first();
                if ($client) {
                    return $client;
                }
            }
        }

        if ($businessName) {
            $client = Client::whereRaw('LOWER(business_name) = ?', [mb_strtolower(trim($businessName))])->first();
            if ($client) {
                return $client;
            }
        }

        return null;
    }

    /**
     * The CSR previously handling this client: the dedicated client owner
     * first, then the last lead assignment, else creator if that user is a
     * CSR, when that user is still active.
     */
    public static function previousCsr(Client $client): ?User
    {
        $owner = $client->assignedTo()->with('roles')->first();
        if ($owner && $owner->is_active) {
            return $owner;
        }

        $lastLead = Lead::with('assignee')
            ->where('client_id', $client->id)
            ->whereNotNull('assigned_to')
            ->latest('id')
            ->first();

        if ($lastLead && $lastLead->assignee && $lastLead->assignee->is_active) {
            return $lastLead->assignee;
        }

        if ($client->creator && $client->creator->is_active && self::isCsr($client->creator)) {
            return $client->creator;
        }

        return null;
    }

    /**
     * Assign a CSR owner to a freshly created client so every new client is
     * divided equally across the CSR team (least-loaded, ties random).
     * Returns the assigned user or null when no CSR exists.
     */
    public static function assignClient(Client $client): ?User
    {
        $csr = self::pickCsr();
        if ($csr) {
            $client->assigned_to = $csr->id;
            $client->save();
        }

        return $csr;
    }

    /**
     * Pick the least-loaded active CSR. Ties are broken randomly so repeated
     * ties still divide equally instead of always favouring the same user.
     */
    public static function pickCsr(): ?User
    {
        $csrs = User::where('is_active', true)
            ->whereHas('roles', function ($query): void {
                $query->whereIn('name', ['csr_manager', 'team_associate']);
            })
            ->get();

        if ($csrs->isEmpty()) {
            return null;
        }

        $loads = $csrs->mapWithKeys(function (User $csr): array {
            $openLeads = Lead::where('assigned_to', $csr->id)
                ->whereIn('status', ['new', 'contacted', 'qualified', 'quotation'])
                ->count();

            $openClients = Client::where('status', 'lead')
                ->where('assigned_to', $csr->id)
                ->count();

            return [$csr->id => $openLeads + $openClients];
        });

        $min = $loads->min();

        return $csrs->filter(fn (User $csr) => $loads[$csr->id] === $min)->random();
    }

    /**
     * Resolve the CSR for a lead: previous CSR → least-loaded CSR.
     */
    public static function resolve(?Client $client): ?User
    {
        if ($client) {
            $previous = self::previousCsr($client);
            if ($previous) {
                return $previous;
            }
        }

        return self::pickCsr();
    }

    public static function isCsr(User $user): bool
    {
        return $user->roles()->whereIn('name', ['csr_manager', 'team_associate'])->exists();
    }
}
