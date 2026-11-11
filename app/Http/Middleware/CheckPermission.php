<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route-level permission middleware.
 *
 * Usage: ->middleware('permission:invoice.lock')
 */
class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('erp.login')->with('error', 'You must be logged in.');
        }

        if ($user->isSuperAdmin() || $user->hasPermission($permission)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->isXmlHttpRequest()) {
            abort(403, 'You do not have permission to perform this action.');
        }

        return redirect()->back()->with('permission_denied', true);
    }
}