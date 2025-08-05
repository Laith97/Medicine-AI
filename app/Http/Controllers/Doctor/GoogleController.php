<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\GoogleAccount;
use App\Services\GoogleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;
use App\Traits\HandlesEffectiveDoctor;

class GoogleController extends Controller
{
    use HandlesEffectiveDoctor;
    protected $googleService;

    public function __construct(GoogleService $googleService)
    {
        $this->googleService = $googleService;
    }

    /**
     * Redirect to Google for authentication
     */
    public function redirectToGoogle()
    {
        $doctor = $this->getEffectiveDoctor();

        if (!$doctor) {
            return redirect()->back()->withErrors(['error' => 'You must be a doctor to connect Google account.']);
        }

        $authUrl = $this->googleService->getAuthUrl();

        return redirect($authUrl);
    }

    /**
     * Handle the Google OAuth callback
     */
    public function handleGoogleCallback(Request $request)
    {
        $doctor = $this->getEffectiveDoctor();

        if (!$doctor) {
            return redirect()->route('doctor.profile.edit')->withErrors(['error' => 'You must be a doctor to connect Google account.']);
        }

        $authCode = $request->get('code');

        if (!$authCode) {
            return redirect()->route('doctor.profile.edit')->withErrors(['error' => 'Google authentication failed.']);
        }

        try {
            $googleAccount = $this->googleService->handleAuthCallback($authCode, $doctor->id);

            return redirect()->route('doctor.profile.edit')->with('success', 'Google account connected successfully!');
        } catch (Exception $e) {
            return redirect()->route('doctor.profile.edit')->withErrors(['error' => 'Failed to connect Google account: ' . $e->getMessage()]);
        }
    }

    /**
     * Disconnect Google account
     */
    public function disconnectGoogle()
    {
        $doctor = Auth::user()->doctor;

        if (!$doctor) {
            return redirect()->back()->withErrors(['error' => 'You must be a doctor to disconnect Google account.']);
        }

        $googleAccount = $doctor->googleAccount;

        if ($googleAccount) {
            $googleAccount->delete();
        }

        return redirect()->route('doctor.profile.edit')->with('success', 'Google account disconnected successfully!');
    }

    /**
     * Get Google My Business accounts
     */
    public function getAccounts()
    {
        $doctor = Auth::user()->doctor;

        if (!$doctor) {
            return response()->json(['error' => 'You must be a doctor to access this feature.'], 403);
        }

        $googleAccount = $doctor->googleAccount;

        if (!$googleAccount || !$googleAccount->is_active) {
            return response()->json(['error' => 'Google account not connected.'], 400);
        }

        try {
            $accounts = $this->googleService->getAccounts($googleAccount);

            return response()->json([
                'success' => true,
                'accounts' => $accounts
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to fetch Google accounts: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get locations for a Google My Business account
     */
    public function getLocations(Request $request)
    {
        $doctor = Auth::user()->doctor;

        if (!$doctor) {
            return response()->json(['error' => 'You must be a doctor to access this feature.'], 403);
        }

        $accountId = $request->get('account_id');

        if (!$accountId) {
            return response()->json(['error' => 'Account ID is required.'], 400);
        }

        $googleAccount = $doctor->googleAccount;

        if (!$googleAccount || !$googleAccount->is_active) {
            return response()->json(['error' => 'Google account not connected.'], 400);
        }

        try {
            $locations = $this->googleService->getLocations($googleAccount, $accountId);

            return response()->json([
                'success' => true,
                'locations' => $locations
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to fetch Google locations: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Set the Google My Business account and location
     */
    public function setAccountLocation(Request $request)
    {
        $doctor = Auth::user()->doctor;

        if (!$doctor) {
            return response()->json(['error' => 'You must be a doctor to access this feature.'], 403);
        }

        $request->validate([
            'account_id' => 'required|string',
            'location_id' => 'required|string',
        ]);

        $googleAccount = $doctor->googleAccount;

        if (!$googleAccount || !$googleAccount->is_active) {
            return response()->json(['error' => 'Google account not connected.'], 400);
        }

        try {
            $this->googleService->updateAccountLocation($googleAccount, $request->account_id, $request->location_id);

            return response()->json([
                'success' => true,
                'message' => 'Google account and location set successfully!'
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to set Google account and location: ' . $e->getMessage()], 500);
        }
    }
}
