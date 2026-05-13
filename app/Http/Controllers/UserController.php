<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    // 🔐 Optional: controller level security
    public function __construct()
    {
        // Route-level middleware already enforces authentication + permissions.
        // Keep controller guard-specific to avoid blocking super-admin (and any role granted users.* permissions).
        $this->middleware('auth:web');
    }

    // =========================
    // 1️⃣ List all users
    // =========================
    public function index()
    {
        $currentUser = auth()->user();

        $users = User::with(['branch', 'roles'])
            ->when(!user_can_manage_all_branches($currentUser), function ($query) use ($currentUser) {
                $query->where('branch_id', user_branch_id($currentUser));
            })
            ->get();
        return view('users.index', compact('users'));
    }


public function permissions(User $user)
{
    $permissions = \Spatie\Permission\Models\Permission::all();
    return view('users.role_permissions', compact('user', 'permissions'));
}

    // =========================
    // 2️⃣ Show create form
    // =========================
    public function create()
    {
        $currentUser = auth()->user();
        $branches = user_can_manage_all_branches($currentUser)
            ? Branch::all()
            : Branch::where('id', user_branch_id($currentUser))->get();
        $roles = user_can_manage_all_branches($currentUser)
            ? Role::all()
            : Role::whereNotIn('name', ['admin', 'super-admin', 'view-only-admin'])->get();

        return view('users.create', compact('branches', 'roles'));
    }

    // =========================
    // 3️⃣ Store new user
    // =========================
    public function store(Request $request)
    {
        $role = $request->input('role');

        // Branch is optional for site-wide roles
        $branchRules = in_array($role, ['admin', 'super-admin'], true)
            ? 'nullable|exists:branches,id'
            : 'required|exists:branches,id';

        $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users',
            'password'  => 'required|min:6',
            'branch_id' => $branchRules,
            'role'      => user_can_manage_all_branches() ? 'required|exists:roles,name' : ['required', Rule::notIn(['admin', 'super-admin', 'view-only-admin']), 'exists:roles,name'],
        ]);

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'branch_id' => in_array($role, ['admin', 'super-admin'], true) ? null : $request->branch_id,
        ]);

        // ✅ Spatie role assign
        $user->assignRole($request->role);

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    // =========================
    // 4️⃣ Show edit form
    // =========================
    public function edit($id)
    {
        $user     = User::with('roles')->findOrFail($id);
        $currentUser = auth()->user();
        $branches = user_can_manage_all_branches($currentUser)
            ? Branch::all()
            : Branch::where('id', user_branch_id($currentUser))->get();
        $roles = user_can_manage_all_branches($currentUser)
            ? Role::all()
            : Role::whereNotIn('name', ['admin', 'super-admin', 'view-only-admin'])->get();

        return view('users.edit', compact('user', 'branches', 'roles'));
    }

    // =========================
    // 5️⃣ Update user
    // =========================
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $role = $request->input('role');

        $branchRules = in_array($role, ['admin', 'super-admin'], true)
            ? 'nullable|exists:branches,id'
            : 'required|exists:branches,id';

        $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email,' . $user->id,
            'branch_id' => $branchRules,
            'role'      => user_can_manage_all_branches() ? 'required|exists:roles,name' : ['required', Rule::notIn(['admin', 'super-admin', 'view-only-admin']), 'exists:roles,name'],
            'password'  => 'nullable|min:6',
        ]);

        $user->update([
            'name'      => $request->name,
            'email'     => $request->email,
            'branch_id' => in_array($role, ['admin', 'super-admin'], true) ? null : $request->branch_id,
        ]);

        // 🔐 Password update (optional)
        if ($request->filled('password')) {
            $user->update([
                'password' => Hash::make($request->password),
            ]);
        }

        // ✅ Role sync (old role remove + new role add)
        $user->syncRoles([$request->role]);

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    // =========================
    // 6️⃣ Delete user
    // =========================
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if (!user_can_manage_all_branches() && (int) $user->branch_id !== (int) user_branch_id()) {
            return back()->with('error', 'You can only delete users from your own branch.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }
}
