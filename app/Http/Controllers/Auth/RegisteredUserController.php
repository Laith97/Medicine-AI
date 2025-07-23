<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'specialty' => ['required', 'string', 'max:255'],
            'custom_specialty' => ['nullable', 'string', 'max:255'],
        ]);

        // Determine the final specialty value
        $specialty = $request->specialty;
        
        // If specialty is empty but custom_specialty is provided, use custom_specialty
        if (empty($specialty) && !empty($request->custom_specialty)) {
            $specialty = trim($request->custom_specialty);
        }
        
        // Validate that we have a specialty
        if (empty($specialty)) {
            return back()->withErrors(['specialty' => 'Please select or enter your medical specialty.'])->withInput();
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Create user settings with selected specialty
        $user->setting()->create([
            'specialty' => $specialty,
            'criterion' => 'CDC', // Default criterion
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
