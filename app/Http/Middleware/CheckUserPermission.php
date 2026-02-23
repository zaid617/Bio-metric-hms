<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckUserPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $permission)
    {
        // 🔹 Try doctor guard first, fallback to web guard
        $user = $request->user('doctor') ?? $request->user('web');

        if (!$user) {
            abort(403, 'User not authenticated.');
        }

        // 🔹 Step 1: Check denied permissions first (polymorphic)
        if ($user->deniedPermissions()->where('name', $permission)->exists()) {
            abort(403, 'User explicitly denied this permission.');
        }

        // 🔹 Step 2: Check direct permission assigned to user
        if (method_exists($user, 'hasDirectPermission') && $user->hasDirectPermission($permission)) {
            return $next($request);
        }

        // 🔹 Step 3: Check role-based / general permissions
        if (method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo($permission)) {
            return $next($request);
        }

        // 🔹 No permission → deny
        abort(403, 'User does not have the right permissions.');
    }
}
