<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, ...$roles)
    {
        // Try both guards
        $user = $request->user('doctor') ?? $request->user('web');

        if (!$user) {
            return redirect()->route('login');
        }

        // Check if user has any of the allowed roles
        if (in_array($user->role, $roles)) {
            return $next($request);
        }

        // User doesn't have required role
        abort(403, 'Unauthorized. Required role: ' . implode(' or ', $roles));
    }
}
