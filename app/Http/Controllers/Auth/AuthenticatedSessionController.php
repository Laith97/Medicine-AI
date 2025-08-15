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
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Redirect based on user role
        $user = Auth::user();

        if ($user->role === 'doctor') {
            return redirect()->intended(route('dashboard', absolute: false));
        } elseif ($user->role === 'admin') {
            return redirect()->intended(route('admin.dashboard', absolute: false));
        } elseif ($user->role === 'hospital_admin') {
            return redirect()->intended(route('hospital-admin.dashboard', absolute: false));
        } else {
            // For patients, redirect to doctors search page
            return redirect()->intended(route('doctors.index', absolute: false));
        }
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
