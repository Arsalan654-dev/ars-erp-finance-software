<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces ERP security requirements:
 *  - Single active session per user
 *  - Idle session timeout
 *  - Inactive / disabled account blocking
 */
class SessionSecurity
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return $next($request);
        }

        // Block disabled accounts
        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('erp.login')->with('error', 'Your account has been deactivated. Contact your administrator.');
        }

        // Idle timeout (default 30 minutes)
        $idleTimeout = (int) config('erp.session_idle_timeout', 30); // minutes
        if ($user->last_activity_at) {
            $idleSeconds = now()->diffInSeconds($user->last_activity_at);
            if ($idleSeconds > ($idleTimeout * 60)) {
                $this->expireSession($request, 'Session timed out due to inactivity.');

                return redirect()->route('erp.login');
            }
        }

        // Single active session enforcement
        $sessionId = $request->session()->getId();
        if ($user->active_session_id && $user->active_session_id !== $sessionId) {
            // Another session is active for this user — log out this one
            $this->expireSession($request, 'You were logged out because you logged in from another device.');

            return redirect()->route('erp.login');
        }

        // Stamp activity
        $user->update([
            'active_session_id' => $sessionId,
            'last_activity_at' => now(),
        ]);

        return $next($request);
    }

    private function expireSession(Request $request, string $reason): void
    {
        $user = Auth::user();
        if ($user) {
            \App\Models\SecurityEvent::create([
                'user_id' => $user->id,
                'event_type' => 'SESSION_EXPIRED',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => ['reason' => $reason],
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        session()->flash('error', $reason);
    }
}