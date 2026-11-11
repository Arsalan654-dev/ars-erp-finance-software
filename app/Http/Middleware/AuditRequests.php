<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records every non-GET request into the append-only audit log.
 */
class AuditRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $user = $request->user();

            // Derive entity type from the route
            $route = $request->route();
            $entityType = null;
            $entityId = null;

            if ($route && $route->getName()) {
                $segments = explode('.', $route->getName());
                $entityType = $segments[count($segments) - 2] ?? null;
            }

            // Try to detect a route-model-bound id in parameters
            if ($route) {
                foreach ($route->parameters() as $value) {
                    if (is_object($value) && isset($value->id)) {
                        $entityId = $value->id;
                        break;
                    }
                    if (is_numeric($value)) {
                        $entityId = $value;
                    }
                }
            }

            AuditLog::create([
                'user_id' => $user->id ?? null,
                'action' => $request->method().' '.($route ? $route->getName() : $request->path()),
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'ip_address' => $request->ip(),
                'user_agent' => substr($request->userAgent() ?? '', 0, 500),
                'metadata' => [
                    'path' => $request->path(),
                    'method' => $request->method(),
                    'query' => $request->query(),
                    'body_keys' => array_keys($request->all()),
                ],
            ]);
        }

        return $response;
    }
}