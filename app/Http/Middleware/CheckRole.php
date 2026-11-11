<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route-level RBAC middleware.
 *
 * Usage: ->middleware('role:finance')  OR  'role:super_admin,finance'
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('erp.login')->with('error', 'You must be logged in.');
        }

        if ($user->hasAnyRole($roles)) {
            return $next($request);
        }

        abort(403, 'You do not have permission to access this page.');
    }
}