<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckUserPermission
{
    public function handle(Request $request, Closure $next, $permission)
    {
        $user = $request->user('doctor') ?? $request->user('web');

        if (!$user) {
            abort(403, 'User not authenticated.');
        }

        // Explicitly denied permissions block access even if role grants them
        if ($user->deniedPermissions()->where('name', $permission)->exists()) {
            abort(403, 'Access denied.');
        }

        if (method_exists($user, 'hasDirectPermission') && $user->hasDirectPermission($permission)) {
            return $next($request);
        }

        if (method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo($permission)) {
            return $next($request);
        }

        abort(403, 'You do not have permission to perform this action.');
    }
}
