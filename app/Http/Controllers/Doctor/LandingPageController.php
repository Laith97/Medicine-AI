<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\DoctorLandingPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LandingPageController extends Controller
{
    /**
     * Display the landing page management interface
     */
    public function index()
    {
        $doctor = auth()->user()->doctor;

        if (!$doctor) {
            return redirect()->route('dashboard')->with('error', 'Doctor profile not found.');
        }

        $landingPage = $doctor->landingPage;

        // Create default landing page if it doesn't exist
        if (!$landingPage) {
            $landingPage = $this->createDefaultLandingPage($doctor);
        }

        return view('doctor.landing-page.index', compact('landingPage'));
    }

    /**
     * Update the landing page settings
     */
    public function update(Request $request)
    {
        $doctor = auth()->user()->doctor;

        if (!$doctor) {
            return response()->json(['error' => 'Doctor profile not found.'], 404);
        }

        $request->validate([
            'username' => 'required|string|max:50|regex:/^[a-zA-Z0-9_-]+$/|unique:doctor_landing_pages,username,' . ($doctor->landingPage->id ?? 'NULL'),
            'template' => 'required|in:template1,template2',
            'page_title' => 'nullable|string|max:255',
            'page_description' => 'nullable|string|max:500',
            'tagline' => 'nullable|string|max:255',
            'about_text' => 'nullable|string|max:2000',
            'colors' => 'nullable|array',
            'section_visibility' => 'nullable|array',
            'custom_domain' => 'nullable|string|max:255|regex:/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            'subdomain_enabled' => 'boolean',
        ]);

        $landingPage = $doctor->landingPage ?: new DoctorLandingPage(['doctor_id' => $doctor->id]);

        $landingPage->fill($request->only([
            'username', 'template', 'page_title', 'page_description',
            'tagline', 'about_text', 'custom_domain', 'subdomain_enabled'
        ]));

        // Handle colors
        if ($request->has('colors')) {
            $landingPage->colors = $request->colors;
        }

        // Handle section visibility
        if ($request->has('section_visibility')) {
            $landingPage->section_visibility = $request->section_visibility;
        }

        $landingPage->save();

        return response()->json([
            'success' => true,
            'message' => 'Landing page updated successfully!',
            'preview_url' => route('doctor.landing.preview', $landingPage->username)
        ]);
    }

    /**
     * Upload hero image
     */
    public function uploadHeroImage(Request $request)
    {
        $request->validate([
            'hero_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $doctor = auth()->user()->doctor;

        if (!$doctor || !$doctor->landingPage) {
            return response()->json(['error' => 'Landing page not found.'], 404);
        }

        $landingPage = $doctor->landingPage;

        // Delete old image if exists
        if ($landingPage->hero_image) {
            Storage::disk('public')->delete($landingPage->hero_image);
        }

        // Store new image
        $path = $request->file('hero_image')->store('landing-pages/hero', 'public');
        $landingPage->hero_image = $path;
        $landingPage->save();

        return response()->json([
            'success' => true,
            'message' => 'Hero image uploaded successfully!',
            'image_url' => Storage::url($path)
        ]);
    }

    /**
     * Toggle landing page publication status
     */
    public function togglePublish(Request $request)
    {
        $doctor = auth()->user()->doctor;

        if (!$doctor || !$doctor->landingPage) {
            return response()->json(['error' => 'Landing page not found.'], 404);
        }

        $landingPage = $doctor->landingPage;
        $landingPage->is_published = !$landingPage->is_published;
        $landingPage->save();

        return response()->json([
            'success' => true,
            'is_published' => $landingPage->is_published,
            'message' => $landingPage->is_published ?
                'Landing page published successfully!' :
                'Landing page unpublished successfully!',
            'public_url' => $landingPage->is_published ? $landingPage->url : null
        ]);
    }

    /**
     * Preview the landing page
     */
    public function preview($username)
    {
        $landingPage = DoctorLandingPage::with(['doctor.user', 'doctor.specialty'])
            ->byUsername($username)
            ->first();

        if (!$landingPage) {
            abort(404, 'Landing page not found.');
        }

        // Check if user owns this landing page or is admin
        if (auth()->check() &&
            (auth()->user()->doctor->id !== $landingPage->doctor_id && !auth()->user()->isAdmin())) {
            abort(403, 'Unauthorized access.');
        }

        return $this->renderLandingPage($landingPage, true);
    }

    /**
     * Create default landing page for doctor
     */
    private function createDefaultLandingPage($doctor)
    {
        $username = $this->generateUniqueUsername($doctor->user->name);

        return DoctorLandingPage::create([
            'doctor_id' => $doctor->id,
            'username' => $username,
            'template' => 'template1',
            'page_title' => $doctor->user->name . ' - ' . ($doctor->specialty->name ?? 'Medical Professional'),
            'page_description' => 'Book an appointment with ' . $doctor->user->name,
            'tagline' => 'Your Health, Our Priority',
            'about_text' => $doctor->bio ?? 'Experienced medical professional dedicated to providing quality healthcare.',
            'colors' => [],
            'section_visibility' => [],
            'is_published' => false,
        ]);
    }

    /**
     * Generate unique username from doctor name
     */
    private function generateUniqueUsername($name)
    {
        $baseUsername = Str::slug(strtolower($name), '');
        $username = $baseUsername;
        $counter = 1;

        while (DoctorLandingPage::where('username', $username)->exists()) {
            $username = $baseUsername . $counter;
            $counter++;
        }

        return $username;
    }

    /**
     * Render landing page with template
     */
    private function renderLandingPage($landingPage, $isPreview = false)
    {
        $doctor = $landingPage->doctor;
        $reviews = $doctor->approvedReviews()->with('patient')->latest()->limit(6)->get();

        // Get available slots for next 7 days
        $availableSlots = [];
        for ($i = 0; $i < 7; $i++) {
            $date = now()->addDays($i)->format('Y-m-d');
            $slots = $doctor->getAvailableSlots($date);
            if ($slots->isNotEmpty()) {
                $availableSlots[$date] = $slots->take(6); // Limit slots per day
            }
        }

        $templateView = 'doctor.landing-page.templates.' . $landingPage->template;

        return view($templateView, compact('landingPage', 'doctor', 'reviews', 'availableSlots', 'isPreview'));
    }
}
