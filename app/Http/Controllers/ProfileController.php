<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Separate notification preferences from user data
        $notificationPreferences = [];
        $userFields = ['name', 'email', 'phone']; // Keep only user-specific fields

        foreach ($validated as $key => $value) {
            if (in_array($key, $userFields)) {
                continue; // These go to user update
            } elseif (str_starts_with($key, 'whatsapp_')) {
                $notificationPreferences[$key] = $value;
                unset($validated[$key]);
            }
        }

        // Update user data
        $request->user()->fill($validated);

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        // Update notification preferences if provided
        if (!empty($notificationPreferences)) {
            $userNotificationPreferences = $request->user()->getOrCreateNotificationPreferences();
            $userNotificationPreferences->update([
                'whatsapp_enabled' => $notificationPreferences['whatsapp_enabled'] ?? false,
                'whatsapp_appointment_reminders' => $notificationPreferences['whatsapp_appointment_reminders'] ?? false,
                'whatsapp_urgent_alerts' => $notificationPreferences['whatsapp_urgent_alerts'] ?? false,
                'whatsapp_diagnosis_updates' => $notificationPreferences['whatsapp_diagnosis_updates'] ?? false,
                'whatsapp_review_requests' => $notificationPreferences['whatsapp_review_requests'] ?? false,
            ]);
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
