<?php

namespace App\Services;

/**
 * Sensitive data handling: AES-256 at rest via Laravel's encrypter,
 * deterministic hashing for search, and role-based masking.
 */
class SensitiveDataService
{
    public const SENSITIVE_FIELDS = ['ein', 'itin', 'utr', 'ssn'];

    /**
     * Encrypt a value for storage (AES-256-CBC via APP_KEY).
     */
    public static function encrypt(?string $value): ?string
    {
        return $value === null || $value === '' ? null : encrypt($value);
    }

    /**
     * Decrypt a stored value.
     */
    public static function decrypt(?string $value): ?string
    {
        if (! $value) {
            return null;
        }
        try {
            return decrypt($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Deterministic hash for exact-match search on sensitive values.
     */
    public static function hash(string $value): string
    {
        return hash('sha256', strtolower(trim($value)));
    }

    /**
     * Mask a sensitive value for non-privileged roles: "123-45-6789" -> "XX-XXX6789"
     */
    public static function mask(?string $value): ?string
    {
        if (! $value) {
            return null;
        }
        $digits = preg_replace('/\D/', '', $value);
        if (strlen($digits) <= 4) {
            return str_repeat('X', strlen($digits));
        }

        return str_repeat('X', max(2, strlen($digits) - 4)).substr($digits, -4);
    }

    /**
     * Whether the current user may view unmasked sensitive data.
     */
    public static function canViewSensitive(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->isSuperAdmin() || $user->hasPermission('sensitive.view');
    }
}