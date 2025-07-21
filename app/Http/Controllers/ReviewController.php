<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    /**
     * Display patient's reviews
     */
    public function index()
    {
        $reviews = Auth::user()->reviews()
            ->with(['doctor.user', 'doctor.specialty', 'appointment'])
            ->latest()
            ->paginate(10);

        return view('reviews.index', compact('reviews'));
    }

    /**
     * Show the form for creating a new review
     */
    public function create(Appointment $appointment)
    {
        // Check if user can review this appointment
        if ($appointment->patient_id !== Auth::id()) {
            abort(403);
        }

        // Check if appointment is completed
        if ($appointment->status !== 'completed') {
            return redirect()->back()->withErrors(['error' => 'You can only review completed appointments.']);
        }

        // Check if review already exists
        if ($appointment->review) {
            return redirect()->route('reviews.show', $appointment->review)
                ->with('info', 'You have already reviewed this appointment.');
        }

        $appointment->load(['doctor.user', 'doctor.specialty']);

        return view('reviews.create', compact('appointment'));
    }

    /**
     * Store a newly created review
     */
    public function store(Request $request)
    {
        $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'is_anonymous' => 'boolean',
            'consent_google_posting' => 'boolean',
        ]);

        $appointment = Appointment::findOrFail($request->appointment_id);

        // Check if user can review this appointment
        if ($appointment->patient_id !== Auth::id()) {
            abort(403);
        }

        // Check if appointment is completed
        if ($appointment->status !== 'completed') {
            return back()->withErrors(['error' => 'You can only review completed appointments.']);
        }

        // Check if review already exists
        if ($appointment->review) {
            return redirect()->route('reviews.show', $appointment->review)
                ->with('info', 'You have already reviewed this appointment.');
        }

        $review = Review::create([
            'doctor_id' => $appointment->doctor_id,
            'patient_id' => Auth::id(),
            'appointment_id' => $appointment->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'is_anonymous' => $request->boolean('is_anonymous'),
            'is_approved' => true, // Auto-approve for now
            'source' => 'medcura',
        ]);

        // TODO: If consent_google_posting is true, queue job to post to Google Reviews

        return redirect()->route('reviews.show', $review)
            ->with('success', 'Thank you for your review!');
    }

    /**
     * Display the specified review
     */
    public function show(Review $review)
    {
        // Check if user can view this review
        if ($review->patient_id !== Auth::id() &&
            (!Auth::user()->isDoctor() || $review->doctor->user_id !== Auth::id())) {
            abort(403);
        }

        $review->load(['doctor.user', 'doctor.specialty', 'patient', 'appointment']);

        return view('reviews.show', compact('review'));
    }

    /**
     * Show the form for editing the specified review
     */
    public function edit(Review $review)
    {
        // Check if user can edit this review
        if ($review->patient_id !== Auth::id()) {
            abort(403);
        }

        // Check if review can be edited (within 24 hours)
        if ($review->created_at->diffInHours(now()) > 24) {
            return back()->withErrors(['error' => 'Reviews can only be edited within 24 hours of posting.']);
        }

        $review->load(['doctor.user', 'doctor.specialty', 'appointment']);

        return view('reviews.edit', compact('review'));
    }

    /**
     * Update the specified review
     */
    public function update(Request $request, Review $review)
    {
        // Check if user can edit this review
        if ($review->patient_id !== Auth::id()) {
            abort(403);
        }

        // Check if review can be edited (within 24 hours)
        if ($review->created_at->diffInHours(now()) > 24) {
            return back()->withErrors(['error' => 'Reviews can only be edited within 24 hours of posting.']);
        }

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'is_anonymous' => 'boolean',
        ]);

        $review->update([
            'rating' => $request->rating,
            'comment' => $request->comment,
            'is_anonymous' => $request->boolean('is_anonymous'),
        ]);

        return redirect()->route('reviews.show', $review)
            ->with('success', 'Review updated successfully!');
    }

    /**
     * Remove the specified review
     */
    public function destroy(Review $review)
    {
        // Check if user can delete this review
        if ($review->patient_id !== Auth::id()) {
            abort(403);
        }

        // Check if review can be deleted (within 24 hours)
        if ($review->created_at->diffInHours(now()) > 24) {
            return back()->withErrors(['error' => 'Reviews can only be deleted within 24 hours of posting.']);
        }

        $review->delete();

        return redirect()->route('reviews.index')
            ->with('success', 'Review deleted successfully.');
    }

    /**
     * Display reviews for a specific doctor (public view)
     */
    public function doctorReviews(Doctor $doctor)
    {
        $reviews = $doctor->approvedReviews()
            ->with(['patient'])
            ->latest()
            ->paginate(10);

        $doctor->load(['user', 'specialty']);

        $ratingStats = [
            'average' => $doctor->average_rating,
            'total' => $doctor->total_reviews,
            'breakdown' => []
        ];

        // Get rating breakdown
        for ($i = 1; $i <= 5; $i++) {
            $count = $doctor->approvedReviews()->where('rating', $i)->count();
            $percentage = $doctor->total_reviews > 0 ? ($count / $doctor->total_reviews) * 100 : 0;
            $ratingStats['breakdown'][$i] = [
                'count' => $count,
                'percentage' => round($percentage, 1)
            ];
        }

        return view('reviews.doctor', compact('doctor', 'reviews', 'ratingStats'));
    }

    /**
     * Get reviews for a doctor (AJAX)
     */
    public function getDoctorReviews(Request $request, Doctor $doctor)
    {
        $query = $doctor->approvedReviews()->with(['patient']);

        // Filter by rating
        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'latest');
        switch ($sortBy) {
            case 'oldest':
                $query->oldest();
                break;
            case 'highest_rating':
                $query->orderBy('rating', 'desc');
                break;
            case 'lowest_rating':
                $query->orderBy('rating', 'asc');
                break;
            default:
                $query->latest();
        }

        $reviews = $query->paginate(10);

        return response()->json([
            'success' => true,
            'reviews' => $reviews->items(),
            'pagination' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ]
        ]);
    }
}
