<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

class LoginController extends Controller
{
    /**
     * Show login form with dynamic roles from DB
     */
    public function showLoginForm()
    {
        $roles = Role::where('guard_name', 'web')->get();
        return view('auth.login', compact('roles'));
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        // Validate input
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'role' => 'required|string',
        ]);

        $role = $request->role;

        // Select guard based on role
        $guard = $role === 'doctor' ? 'doctor' : 'web';

        $credentials = $request->only('email', 'password');

        // Attempt login
        if (Auth::guard($guard)->attempt($credentials, $request->filled('remember'))) {

            $user = Auth::guard($guard)->user();

            // Ensure the user has the selected role (Spatie)
            if (!$user->hasRole($role)) {
                Auth::guard($guard)->logout();
                return back()->withErrors([
                    'role' => 'You are not authorized to login as this role.'
                ])->withInput($request->only('email','role'));
            }

            // Role-based redirects
            $redirectRoute = match ($role) {
                'doctor' => 'doctor.dashboard',
                'admin' => 'admin.dashboard',
                'view-only-admin' => 'view-only-admin.dashboard',
                'receptionist' => 'receptionist.dashboard',
                'manager' => 'manager.dashboard',
                'accountant' => 'accountant.dashboard',
                'pharmacist' => 'pharmacist.dashboard',
                default => 'home',
            };

            if (!Route::has($redirectRoute)) {
                $redirectRoute = 'home';
            }

            return redirect()->route($redirectRoute);
        }

        // Invalid credentials
        return back()->withErrors([
            'email' => 'These credentials do not match our records.',
        ])->withInput($request->only('email','role'));
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        // Determine guard based on role if sent, else default to web
        $role = $request->role ?? 'web';
        $guard = $role === 'doctor' ? 'doctor' : 'web';

        Auth::guard($guard)->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
}
