<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Specialty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DoctorController extends Controller
{
    /**
     * Display a listing of doctors with search and filters
     */
    public function index(Request $request)
    {
        // Debug logging for doctor counts
        Log::info('Doctor counts debug', [
            'total_doctors' => Doctor::count(),
            'active_doctors' => Doctor::active()->count(),
            'verified_doctors' => Doctor::verified()->count(),
            'active_verified_doctors' => Doctor::active()->verified()->count(),
        ]);

        $query = Doctor::with(['user', 'specialty'])
            ->active()
            ->verified();

        // Search by name or specialty
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })->orWhereHas('specialty', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        // Filter by specialty
        if ($request->filled('specialty')) {
            $query->where('specialty_id', $request->specialty);
        }

        // Filter by location (city)
        if ($request->filled('city')) {
            $query->where('city', 'like', "%{$request->city}%");
        }

        // Filter by language
        if ($request->filled('language')) {
            $query->whereJsonContains('languages', $request->language);
        }

        // Filter by rating
        if ($request->filled('min_rating')) {
            $query->where('average_rating', '>=', $request->min_rating);
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'average_rating');
        $sortOrder = $request->get('sort_order', 'desc');

        switch ($sortBy) {
            case 'name':
                $query->join('users', 'doctors.user_id', '=', 'users.id')
                      ->orderBy('users.name', $sortOrder)
                      ->select('doctors.*');
                break;
            case 'rating':
                $query->orderBy('average_rating', $sortOrder);
                break;
            case 'reviews':
                $query->orderBy('total_reviews', $sortOrder);
                break;
            case 'fee':
                $query->orderBy('consultation_fee', $sortOrder);
                break;
            default:
                $query->orderBy('average_rating', 'desc');
        }

        $doctors = $query->paginate(12)->withQueryString();

        // Log doctor listing for debugging visibility
        Log::info('Doctors listed for patients', [
            'total_doctors_found' => $doctors->total(),
            'doctors_on_page' => $doctors->count(),
            'current_page' => $doctors->currentPage(),
            'filters_applied' => [
                'search' => $request->search,
                'specialty' => $request->specialty,
                'city' => $request->city,
                'language' => $request->language,
                'min_rating' => $request->min_rating,
                'sort_by' => $request->get('sort_by', 'average_rating'),
                'sort_order' => $request->get('sort_order', 'desc'),
            ],
            'sample_doctor_ids' => $doctors->pluck('id')->take(5)->toArray(),
        ]);

        $specialties = Specialty::active()->orderBy('name')->get();
        $languages = $this->getAvailableLanguages();
        $cities = $this->getAvailableCities();

        return view('doctors.index', compact('doctors', 'specialties', 'languages', 'cities'));
    }

    /**
     * Display the specified doctor profile
     */
    public function show(Doctor $doctor)
    {
        // Make sure we're not returning a single availability slot
        if (request()->has('date')) {
            return $this->getAvailableSlots(request(), $doctor);
        }

        $doctor->load([
            'user',
            'specialty',
            'approvedReviews' => function ($query) {
                $query->with('patient')->latest()->limit(10);
            },
            'availabilitySlots' => function ($query) {
                $query->active()->orderBy('day_of_week');
            }
        ]);

        // Get available slots for the next 7 days
        $availableSlots = [];
        for ($i = 0; $i < 7; $i++) {
            $date = now()->addDays($i)->format('Y-m-d');
            $slots = $doctor->getAvailableSlots($date);
            if ($slots->isNotEmpty()) {
                $availableSlots[$date] = $slots;
            }
        }
Log::info('Doctor availability slots:', $doctor->availabilitySlots->toArray());
Log::info('Available slots for next 7 days:', $availableSlots);

        return view('doctors.show', compact('doctor', 'availableSlots'));
    }

    /**
     * Get available slots for a specific doctor and date (AJAX)
     */
    public function getAvailableSlots(Request $request, Doctor $doctor)
    {
        $request->validate([
            'date' => 'required|date|after_or_equal:today'
        ]);

        $slots = $doctor->getAvailableSlots($request->date);

        return response()->json([
            'success' => true,
            'slots' => $slots,
            'date' => $request->date
        ]);
    }

    /**
     * Get available languages from all doctors
     */
    private function getAvailableLanguages()
    {
        $languages = Doctor::active()
            ->whereNotNull('languages')
            ->pluck('languages')
            ->flatten()
            ->unique()
            ->sort()
            ->values();

        return $languages;
    }

    /**
     * Get available cities from all doctors
     */
    private function getAvailableCities()
    {
        return Doctor::active()
            ->whereNotNull('city')
            ->distinct()
            ->pluck('city')
            ->sort()
            ->values();
    }

    /**
     * Search doctors (AJAX endpoint)
     */
    public function search(Request $request)
    {
        $query = Doctor::with(['user', 'specialty'])
            ->active()
            ->verified();

        if ($request->filled('q')) {
            $search = $request->q;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })->orWhereHas('specialty', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $doctors = $query->limit(10)->get();

        return response()->json([
            'success' => true,
            'doctors' => $doctors->map(function ($doctor) {
                return [
                    'id' => $doctor->id,
                    'name' => $doctor->user->name,
                    'specialty' => $doctor->specialty->name,
                    'city' => $doctor->city,
                    'rating' => $doctor->average_rating,
                    'reviews' => $doctor->total_reviews,
                    'profile_image' => $doctor->profile_image,
                    'url' => route('doctors.show', $doctor)
                ];
            })
        ]);
    }
}
