<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class LoginController extends Controller
{
    /**
     * Show login form
     */
    public function showLoginForm()
    {
        return view('auth.login');
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
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        // Attempt doctor login first (separate guard/provider)
        if (Auth::guard('doctor')->attempt($credentials, $remember)) {
            $request->session()->regenerate();

            $redirectRoute = 'doctor.dashboard';
            return Route::has($redirectRoute)
                ? redirect()->route($redirectRoute)
                : redirect()->route('home');
        }

        // Attempt normal (web) login
        if (Auth::guard('web')->attempt($credentials, $remember)) {
            $request->session()->regenerate();

            $user = Auth::guard('web')->user();
            $redirectRoute = $this->resolveWebDashboardRoute($user);

            if (!Route::has($redirectRoute)) {
                $redirectRoute = 'home';
            }

            return redirect()->route($redirectRoute);
        }

        // Invalid credentials
        return back()->withErrors([
            'email' => 'These credentials do not match our records.',
        ])->withInput($request->only('email'));
    }

    /**
     * Resolve where a web-guard user should land after login.
     */
    private function resolveWebDashboardRoute($user): string
    {
        if ($user && ($user->hasRole('admin') || $user->hasRole('super-admin'))) {
            return 'admin.dashboard';
        }
        if ($user && $user->hasRole('view-only-admin')) {
            return 'view-only-admin.dashboard';
        }
        if ($user && $user->hasRole('manager')) {
            return 'manager.dashboard';
        }
        if ($user && $user->hasRole('receptionist')) {
            return 'receptionist.dashboard';
        }

        return 'home';
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        // Logout from both guards to avoid leaving a doctor session active
        Auth::guard('web')->logout();
        Auth::guard('doctor')->logout();

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
