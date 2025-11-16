<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class PatientRegistrationController extends Controller
{
    /**
     * Show the patient registration form
     */
    public function create()
    {
        return view('auth.patient-register');
    }

    /**
     * Handle patient registration
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender' => ['required', 'in:male,female,other'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'terms' => ['required', 'accepted'],
        ]);

        // Calculate age from date of birth
        $age = \Carbon\Carbon::parse($request->date_of_birth)->age;

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'date_of_birth' => $request->date_of_birth,
            'age' => $age,
            'gender' => $request->gender,
            'password' => Hash::make($request->password),
            'role' => 'patient',
        ]);

        Auth::login($user);

        return redirect()->route('doctors.index')->with('success', 'Welcome! Your patient account has been created successfully.');
    }
}
