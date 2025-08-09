<?php

namespace App\Http\Controllers\HospitalAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class HospitalController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            
            // Allow access if admin is impersonating
            if (session()->has('impersonating_admin_id')) {
                return $next($request);
            }
            
            // Allow super admin direct access
            if ($user->role === 'admin') {
                return $next($request);
            }
            
            if (!$user->isHospitalAdmin()) {
                abort(403, 'Access denied. Hospital admin role required.');
            }
            
            if (!$user->hospital) {
                abort(403, 'Access denied. Hospital association required.');
            }
            
            return $next($request);
        });
    }

    /**
     * Show hospital profile page
     */
    public function profile()
    {
        $user = Auth::user();
        $hospital = $user->hospital;
        
        // Get hospital statistics
        $doctors = $hospital->doctors()->with('doctor')->get();
        $totalAppointments = 0;
        $totalRating = 0;
        $ratingCount = 0;
        
        foreach ($doctors as $doctor) {
            if ($doctor->doctor) {
                $totalAppointments += $doctor->doctor->appointments()->count();
                $doctorReviews = $doctor->doctor->reviews();
                $totalRating += $doctorReviews->sum('rating');
                $ratingCount += $doctorReviews->count();
            }
        }
        
        $statistics = [
            'total_doctors' => $doctors->count(),
            'active_doctors' => $doctors->filter(function($doctor) {
                return $doctor->doctor && $doctor->doctor->is_active;
            })->count(),
            'total_departments' => $hospital->departments()->count(),
            'total_appointments' => $totalAppointments,
            'average_rating' => $ratingCount > 0 ? $totalRating / $ratingCount : 0,
        ];
        
        return view('hospital-admin.hospital.profile', compact('hospital', 'statistics'));
    }

    /**
     * Update hospital profile
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $hospital = $user->hospital;
        
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->only([
            'name', 'description', 'address', 'city', 'state', 
            'zip_code', 'phone', 'email', 'website'
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($hospital->logo_path && Storage::exists($hospital->logo_path)) {
                Storage::delete($hospital->logo_path);
            }

            $logoPath = $request->file('logo')->store('hospital-logos', 'public');
            $data['logo_path'] = $logoPath;
        }

        $hospital->update($data);

        return back()->with('success', 'Hospital profile updated successfully.');
    }
}