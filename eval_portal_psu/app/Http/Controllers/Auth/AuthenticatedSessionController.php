<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid credentials.']);
        }

        $request->session()->regenerate();

        $user = Auth::user();
        // 🔹 role-based redirection
        if ($user->hasRole('systemadmin')) {
            return redirect()->route('dashboard.systemadmin');
        }

        if ($user->hasRole('ced')) {
            return redirect()->route('dashboard.ced');
        }

        if ($user->hasRole('chairman')) {
            return redirect()->route('dashboard.chairman');
        }

        if ($user->hasRole('instructor')) {
            return redirect()->route('dashboard.instructor');
        }

        if ($user->hasRole('student')) {
            return redirect()->route('dashboard.student');
        }

        // fallback (if user somehow has no role)
        return redirect()->route('dashboard.default');
    }


    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
