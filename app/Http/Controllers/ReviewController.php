<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Jobs\PostReviewToGoogle;
use App\Mail\ReviewVerificationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
    public function create(Request $request, Appointment $appointment = null)
    {
        // If appointment is passed as route parameter (from /appointments/{appointment}/review)
        if ($appointment) {
            // Check if user can review this appointment
            if ($appointment->patient_id !== Auth::id()) {
                abort(403);
            }

            // Check if appointment is completed
            if ($appointment->status !== 'completed') {
                return redirect()->route('appointments.show', $appointment)
                    ->withErrors(['error' => 'You can only review completed appointments.']);
            }

            // Check if review already exists
            if ($appointment->review) {
                return redirect()->route('reviews.show', $appointment->review)
                    ->with('info', 'You have already reviewed this appointment.');
            }

            $appointment->load(['doctor.user', 'doctor.specialty']);
            return view('reviews.create', compact('appointment'));
        }

        // Guest review creation (legacy support)
        $appointmentNumber = $request->route('appointment');
        if ($appointmentNumber && !is_object($appointmentNumber)) {
            $request->validate([
                'email' => 'required|email',
            ]);

            $appointment = Appointment::where('appointment_number', $appointmentNumber)
                ->where('guest_email', $request->email)
                ->with(['doctor.user', 'doctor.specialty'])
                ->firstOrFail();

            // Check if appointment is completed
            if ($appointment->status !== 'completed') {
                return redirect()->back()->withErrors(['error' => 'You can only review completed appointments.']);
            }

            // Check if review already exists
            if ($appointment->review) {
                return redirect()->route('reviews.guest.show', [
                    'appointment' => $appointmentNumber,
                    'email' => $request->email
                ])->with('info', 'You have already reviewed this appointment.');
            }

            return view('reviews.guest.create', compact('appointment'));
        }

        // Fallback for other cases
        if ($request->has('appointment_id')) {
            $appointment = Appointment::findOrFail($request->appointment_id);

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

        abort(404);
    }

    /**
     * Store a newly created review
     */
    public function store(Request $request)
    {
        // Base validation rules
        $rules = [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'is_anonymous' => 'boolean',
            'consent_google_posting' => 'boolean',
        ];

        // Add validation based on user type
        if (Auth::check()) {
            $rules['appointment_id'] = 'required|exists:appointments,id';
        } else {
            $rules = array_merge($rules, [
                'appointment_number' => 'required|string',
                'guest_email' => 'required|email',
                'guest_name' => 'required_unless:is_anonymous,true|string|max:255',
            ]);
        }

        $request->validate($rules);

        if (Auth::check()) {
            // Registered user review
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
                'consent_google_posting' => $request->boolean('consent_google_posting'),
                'is_approved' => true, // Auto-approve for now
                'source' => 'medcura',
            ]);

            // Update doctor's review statistics
            $this->updateDoctorReviewStats($appointment->doctor_id);

            // Google Business posting disabled - stubbed feature not needed in production (was PostReviewToGoogle::dispatch)
            // if ($request->boolean('consent_google_posting')) {
            //     PostReviewToGoogle::dispatch($review->id);
            // }

            return redirect()->route('reviews.show', $review)
                ->with('success', 'Thank you for your review!');
        } else {
            // Guest review
            $appointment = Appointment::where('appointment_number', $request->appointment_number)
                ->where('guest_email', $request->guest_email)
                ->firstOrFail();

            // Check if appointment is completed
            if ($appointment->status !== 'completed') {
                return back()->withErrors(['error' => 'You can only review completed appointments.']);
            }

            // Check if review already exists
            if ($appointment->review) {
                return redirect()->route('reviews.guest.show', [
                    'appointment' => $request->appointment_number,
                    'email' => $request->guest_email
                ])->with('info', 'You have already reviewed this appointment.');
            }

            $review = Review::create([
                'doctor_id' => $appointment->doctor_id,
                'appointment_id' => $appointment->id,
                'rating' => $request->rating,
                'comment' => $request->comment,
                'is_anonymous' => $request->boolean('is_anonymous'),
                'guest_name' => $request->boolean('is_anonymous') ? null : $request->guest_name,
                'guest_email' => $request->guest_email,
                'is_approved' => false, // Require verification for guest reviews
                'source' => 'medcura',
            ]);

            // Generate verification token
            $review->generateVerificationToken();

            // Update doctor's review statistics (for approved reviews only)
            if ($review->is_approved) {
                $this->updateDoctorReviewStats($appointment->doctor_id);
            }

            // Send verification email
            try {
                Mail::to($request->guest_email)->send(new ReviewVerificationMail($review));
            } catch (\Exception $e) {
                Log::error('Failed to send review verification email: ' . $e->getMessage());
                // Continue with the process even if email fails
            }

            return redirect()->route('reviews.guest.verify', [
                'review' => $review->id,
                'email' => $request->guest_email
            ])->with('success', 'Thank you for your review! Please check your email to verify and publish your review.');
        }
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

        // Update doctor's review statistics
        $this->updateDoctorReviewStats($review->doctor_id);

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

        $doctorId = $review->doctor_id;
        $review->delete();

        // Update doctor's review statistics
        $this->updateDoctorReviewStats($doctorId);

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

    /**
     * Show guest review verification form
     */
    public function guestVerify(Request $request, $reviewId)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $review = Review::where('id', $reviewId)
            ->where('guest_email', $request->email)
            ->firstOrFail();

        return view('reviews.guest.verify', compact('review'));
    }

    /**
     * Verify guest review with token
     */
    public function guestVerifyToken(Request $request, $reviewId)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
        ]);

        $review = Review::where('id', $reviewId)
            ->where('guest_email', $request->email)
            ->firstOrFail();

        if ($review->verifyWithToken($request->token)) {
            $review->update(['is_approved' => true]);

            // Update doctor's review statistics now that review is approved
            $this->updateDoctorReviewStats($review->doctor_id);

            return redirect()->route('doctors.show', $review->doctor)
                ->with('success', 'Review verified and published successfully!');
        }

        return back()->withErrors(['token' => 'Invalid or expired verification token.']);
    }

    /**
     * Show guest review
     */
    public function guestShow(Request $request, $appointmentNumber)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $appointment = Appointment::where('appointment_number', $appointmentNumber)
            ->where('guest_email', $request->email)
            ->with(['review', 'doctor.user', 'doctor.specialty'])
            ->firstOrFail();

        if (!$appointment->review) {
            return redirect()->route('reviews.guest.create', [
                'appointment' => $appointmentNumber,
                'email' => $request->email
            ])->with('info', 'You haven\'t reviewed this appointment yet.');
        }

        return view('reviews.guest.show', compact('appointment'));
    }

    /**
     * Update doctor's review statistics
     */
    private function updateDoctorReviewStats($doctorId)
    {
        $doctor = \App\Models\Doctor::find($doctorId);
        if ($doctor) {
            $reviews = $doctor->reviews()->where('is_approved', true);
            $totalReviews = $reviews->count();
            $averageRating = $totalReviews > 0 ? $reviews->avg('rating') : 0;

            $doctor->update([
                'total_reviews' => $totalReviews,
                'average_rating' => round($averageRating, 2)
            ]);
        }
    }

    /**
     * Show guest review creation form
     */
    public function guestCreate(Request $request, $appointmentNumber)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $appointment = Appointment::where('appointment_number', $appointmentNumber)
            ->where('guest_email', $request->email)
            ->where('status', 'completed')
            ->with(['doctor.user', 'doctor.specialty', 'review'])
            ->firstOrFail();

        if ($appointment->review) {
            return redirect()->route('reviews.guest.show', [
                'appointment' => $appointmentNumber,
                'email' => $request->email
            ])->with('info', 'You have already reviewed this appointment.');
        }

        return view('reviews.guest.create', compact('appointment'));
    }

    /**
     * Store guest review
     */
    public function guestStore(Request $request)
    {
        $request->validate([
            'appointment_number' => 'required|string',
            'guest_email' => 'required|email',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'guest_name' => 'nullable|string|max:255',
            'is_anonymous' => 'boolean',
            'consent_google_posting' => 'boolean',
        ]);

        $appointment = Appointment::where('appointment_number', $request->appointment_number)
            ->where('guest_email', $request->guest_email)
            ->where('status', 'completed')
            ->firstOrFail();

        if ($appointment->review) {
            return redirect()->route('reviews.guest.show', [
                'appointment' => $request->appointment_number,
                'email' => $request->guest_email
            ])->with('error', 'You have already reviewed this appointment.');
        }

        // Create the review
        $review = Review::create([
            'doctor_id' => $appointment->doctor_id,
            'patient_id' => null, // Guest review
            'appointment_id' => $appointment->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'guest_name' => $request->is_anonymous ? null : $request->guest_name,
            'guest_email' => $request->guest_email,
            'is_anonymous' => $request->boolean('is_anonymous'),
            'consent_google_posting' => $request->boolean('consent_google_posting'),
            'is_verified' => false, // Requires verification
            'source' => 'guest',
        ]);

        // Generate verification token
        $review->generateVerificationToken();

        // Send verification email
        try {
            Mail::to($request->guest_email)->send(new ReviewVerificationMail($review));
        } catch (\Exception $e) {
            Log::error('Failed to send guest review verification email: ' . $e->getMessage());
            // Continue with the process even if email fails
        }

        // Google Business posting disabled - stubbed feature not needed in production
        // if ($request->boolean('consent_google_posting')) {
        //     PostReviewToGoogle::dispatch($review->id);
        // }

        return redirect()->route('appointments.guest.show', [
            'appointment' => $request->appointment_number,
            'email' => $request->guest_email
        ])->with('success', 'Review submitted! Please check your email to verify and publish your review.');
    }
}
