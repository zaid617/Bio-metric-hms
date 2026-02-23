<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionController extends Controller
{
    // =========================
    // ROLE PERMISSIONS
    // =========================
    public function rolePermissions()
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all();
        return view('role_permissions.roles', compact('roles','permissions'));
    }

    public function updateRolePermission(Request $request)
    {
        $role = Role::findOrFail($request->role_id);
        $permission = Permission::where('name', $request->permission_name)
                                ->where('guard_name', $role->guard_name)
                                ->firstOrFail();

        if ($request->has_permission) {
            $role->givePermissionTo($permission);
        } else {
            $role->revokePermissionTo($permission);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return response()->json(['status'=>'success']);
    }

    // =========================
    // USER PERMISSIONS
    // =========================
    public function userPermissions()
    {
        $users = User::with('roles.permissions','permissions','deniedPermissions')->get();
        $permissions = Permission::all();
        return view('role_permissions.users', compact('users','permissions'));
    }

    public function updateUserPermission(Request $request)
    {
        $user = User::findOrFail($request->user_id);
        $permission = Permission::findOrFail($request->permission_id);

        if ($request->has_permission) {
            $user->givePermissionTo($permission); // Grant
            $user->deniedPermissions()->detach($permission->id); // Remove deny
        } else {
            $user->revokePermissionTo($permission); // Revoke direct
            $user->deniedPermissions()->syncWithoutDetaching([$permission->id]); // Add deny
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return response()->json(['status'=>'success']);
    }

    public function showUserPermissions(User $user)
{
    $permissions = Permission::all();
    return view('users.user_permissions', compact('user', 'permissions'));
}
}
