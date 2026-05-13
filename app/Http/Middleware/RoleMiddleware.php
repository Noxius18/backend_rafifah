<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = auth()->user();

        if (!$user) {
            abort(403, 'Unauthenticated');
        }

        foreach ($roles as $role) {
            if (strtolower($user->jabatan) === strtolower($role)) {
                return $next($request);
            }
        }

        abort(403, 'Akses ditolak. Hanya role: ' . implode(', ', $roles));
    }
}