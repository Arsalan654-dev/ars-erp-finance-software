<?php

namespace App\Services;

use App\Models\AuditLog;

/**
 * Central audit logging service. All sensitive actions go through here.
 */
class AuditService
{
    /**
     * Record an action into the append-only audit log.
     */
    public static function log(
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $metadata = null,
        ?int $userId = null
    ): void {
        AuditLog::create([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'ip_address' => request()->ip(),
            'user_agent' => substr(request()->userAgent() ?? '', 0, 500),
            'metadata' => $metadata,
        ]);
    }
}