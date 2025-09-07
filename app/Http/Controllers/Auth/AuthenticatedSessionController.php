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
        
        // Clear any existing intended URL to prevent redirects
        session()->forget('url.intended');
        
        // Build the redirect URL directly without using route() to avoid any middleware interference
        $redirectUrl = null;
        
        if ($user->role === 'doctor') {
            $redirectUrl = '/dashboard';
        } elseif ($user->role === 'admin') {
            $redirectUrl = '/admin/dashboard';
        } elseif ($user->role === 'hospital_admin') {
            $redirectUrl = '/hospital-admin/dashboard';
        } else {
            // For patients, redirect to doctors search page
            $redirectUrl = '/doctors';
        }
        
        // Use a plain redirect to avoid any route middleware
        return redirect($redirectUrl);
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
