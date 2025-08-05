<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class TestimonialController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'doctor']);
    }

    public function index()
    {
        $reviews = $this->getEffectiveDoctor()->reviews()
                      ->orderBy('created_at', 'desc')
                      ->paginate(12);

        return view('doctor.testimonials.index', compact('reviews'));
    }

    public function togglePublic(Request $request, Review $review)
    {
        // Ensure the review belongs to the authenticated doctor
        if ($review->doctor_id !== $this->getEffectiveDoctor()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $review->update([
            'is_public' => !$review->is_public
        ]);

        $status = $review->is_public ? 'public' : 'private';

        return response()->json([
            'success' => true,
            'is_public' => $review->is_public,
            'message' => "Testimonial marked as {$status} successfully!"
        ]);
    }

    public function updateCaseStudy(Request $request, Review $review)
    {
        // Ensure the review belongs to the authenticated doctor
        if ($review->doctor_id !== $this->getEffectiveDoctor()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'case_study' => 'nullable|string|max:1000',
        ]);

        $review->update([
            'case_study' => $request->case_study
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Case study updated successfully!'
        ]);
    }

    public function getPublicTestimonials($username)
    {
        // This method is for the public API to get testimonials for a doctor's landing page
        $doctor = \App\Models\Doctor::whereHas('landingPage', function ($query) use ($username) {
            $query->where('username', $username);
        })->firstOrFail();

        $testimonials = $doctor->publicReviews()
                              ->orderBy('created_at', 'desc')
                              ->limit(10)
                              ->get()
                              ->map(function ($review) {
                                  return [
                                      'id' => $review->id,
                                      'rating' => $review->rating,
                                      'comment' => $review->comment,
                                      'case_study' => $review->case_study,
                                      'patient_initials' => $this->getPatientInitials($review),
                                      'created_at' => $review->created_at->format('M Y'),
                                      'is_anonymous' => $review->is_anonymous,
                                  ];
                              });

        return response()->json([
            'success' => true,
            'testimonials' => $testimonials
        ]);
    }

    private function getPatientInitials($review)
    {
        if ($review->is_anonymous) {
            return 'A';
        }

        if ($review->patient_name) {
            $names = explode(' ', trim($review->patient_name));
            return count($names) >= 2 ?
                strtoupper(substr($names[0], 0, 1) . substr($names[1], 0, 1)) :
                strtoupper(substr($names[0], 0, 1)) . '.';
        }

        if ($review->user && $review->user->name) {
            $names = explode(' ', trim($review->user->name));
            return count($names) >= 2 ?
                strtoupper(substr($names[0], 0, 1) . substr($names[1], 0, 1)) :
                strtoupper(substr($names[0], 0, 1)) . '.';
        }

        return 'P.';
    }
}
