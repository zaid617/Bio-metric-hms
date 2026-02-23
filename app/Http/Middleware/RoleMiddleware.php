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
    public function handle($request, Closure $next, $role)
    {
        if (!Auth::guard($guard)->check()) {
        return redirect()->route('login'); // or doctor.login if separate
    }


    $user = Auth::guard($guard)->user();

    if ($guard === 'doctor' && $role !== 'doctor') {
        abort(403, 'Unauthorized');
    }

    if ($guard === 'web' && $user->role !== $role) {
        abort(403, 'Unauthorized');
    }

    return $next($request);
    }
}