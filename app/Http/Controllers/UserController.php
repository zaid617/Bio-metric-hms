<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    // 🔐 Optional: controller level security
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin'); // sirf admin user manage kare
    }

    // =========================
    // 1️⃣ List all users
    // =========================
    public function index()
    {
        $users = User::with(['branch', 'roles'])->get();
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
        $branches = Branch::all();
        $roles    = Role::all();

        return view('users.create', compact('branches', 'roles'));
    }

    // =========================
    // 3️⃣ Store new user
    // =========================
    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users',
            'password'  => 'required|min:6',
            'branch_id' => 'required|exists:branches,id',
            'role'      => 'required|exists:roles,name',
        ]);

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'branch_id' => $request->branch_id,
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
        $branches = Branch::all();
        $roles    = Role::all();

        return view('users.edit', compact('user', 'branches', 'roles'));
    }

    // =========================
    // 5️⃣ Update user
    // =========================
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email,' . $user->id,
            'branch_id' => 'required|exists:branches,id',
            'role'      => 'required|exists:roles,name',
            'password'  => 'nullable|min:6',
        ]);

        $user->update([
            'name'      => $request->name,
            'email'     => $request->email,
            'branch_id' => $request->branch_id,
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
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }
}
