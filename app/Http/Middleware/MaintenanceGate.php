<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceGate
{
    /**
     * Roles allowed through while the maintenance gate is on.
     * Hardcoded for now; compared case-insensitively against the user's role.
     */
    protected array $allowedRoles = ['superadmin', 'admin'];

    /**
     * Handle an incoming request.
     *
     * When MAINTENANCE_GATE is enabled, any authenticated user whose role is
     * not in $allowedRoles receives the maintenance page (HTTP 503).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.maintenance_gate')) {
            return $next($request);
        }

        $user = $request->user();
        $role = $user ? strtolower((string) $user->role) : null;

        if (! in_array($role, $this->allowedRoles, true)) {
            return response()->view('maintenance', [], 503);
        }

        return $next($request);
    }
}
