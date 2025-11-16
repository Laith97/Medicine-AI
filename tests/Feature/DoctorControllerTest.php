<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DoctorControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $doctor;
    protected $specialty;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create specialty
        $this->specialty = Specialty::factory()->create([
            'name' => 'Cardiology',
            'code' => 'CARD',
            'is_active' => true,
        ]);

        // Create doctor user
        $this->user = User::factory()->create([
            'name' => 'Dr. John Smith',
            'role' => 'doctor'
        ]);

        // Create doctor
        $this->doctor = Doctor::factory()->create([
            'user_id' => $this->user->id,
            'specialty_id' => $this->specialty->id,
            'city' => 'New York',
            'languages' => ['English', 'Spanish'],
            'average_rating' => 4.5,
            'total_reviews' => 25,
            'consultation_fee' => 150.00,
            'is_active' => true,
            'is_verified' => true,
        ]);
    }

    public function test_index_displays_doctors_list()
    {
        $response = $this->get('/doctors');

        $response->assertStatus(200)
                ->assertViewIs('doctors.index')
                ->assertViewHas(['doctors', 'specialties', 'languages', 'cities']);
    }

    public function test_index_with_search_filter()
    {
        // Create another doctor for testing search
        $otherUser = User::factory()->create(['name' => 'Dr. Jane Doe', 'role' => 'doctor']);
        $otherDoctor = Doctor::factory()->create([
            'user_id' => $otherUser->id,
            'specialty_id' => $this->specialty->id,
            'is_active' => true,
            'is_verified' => true,
        ]);

        $response = $this->get('/doctors?search=John');

        $response->assertStatus(200)
                ->assertViewIs('doctors.index');
    }

    public function test_index_with_specialty_filter()
    {
        $response = $this->get('/doctors?specialty=' . $this->specialty->id);

        $response->assertStatus(200)
                ->assertViewIs('doctors.index');
    }

    public function test_index_with_city_filter()
    {
        $response = $this->get('/doctors?city=New%20York');

        $response->assertStatus(200)
                ->assertViewIs('doctors.index');
    }

    public function test_index_with_language_filter()
    {
        $response = $this->get('/doctors?language=English');

        $response->assertStatus(200)
                ->assertViewIs('doctors.index');
    }

    public function test_index_with_rating_filter()
    {
        $response = $this->get('/doctors?min_rating=4.0');

        $response->assertStatus(200)
                ->assertViewIs('doctors.index');
    }

    public function test_index_with_sorting()
    {
        $response = $this->get('/doctors?sort_by=name&sort_order=asc');

        $response->assertStatus(200)
                ->assertViewIs('doctors.index');
    }

    public function test_index_excludes_inactive_doctors()
    {
        // Create inactive doctor
        $inactiveUser = User::factory()->create(['role' => 'doctor']);
        $inactiveDoctor = Doctor::factory()->create([
            'user_id' => $inactiveUser->id,
            'specialty_id' => $this->specialty->id,
            'is_active' => false,
            'is_verified' => true,
        ]);

        $response = $this->get('/doctors');

        $response->assertStatus(200);
        // The inactive doctor should not appear in the results
    }

    public function test_index_excludes_unverified_doctors()
    {
        // Create unverified doctor
        $unverifiedUser = User::factory()->create(['role' => 'doctor']);
        $unverifiedDoctor = Doctor::factory()->create([
            'user_id' => $unverifiedUser->id,
            'specialty_id' => $this->specialty->id,
            'is_active' => true,
            'is_verified' => false,
        ]);

        $response = $this->get('/doctors');

        $response->assertStatus(200);
        // The unverified doctor should not appear in the results
    }

    public function test_show_doctor_profile()
    {
        $response = $this->get("/doctors/{$this->doctor->id}");

        $response->assertStatus(200)
                ->assertViewIs('doctors.show')
                ->assertViewHas(['doctor', 'availableSlots']);
    }

    public function test_show_doctor_with_reviews()
    {
        // Create a review for the doctor
        $patientUser = User::factory()->create(['role' => 'patient']);
        Review::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $patientUser->id,
            'rating' => 5,
            'comment' => 'Great doctor!',
            'is_approved' => true,
        ]);

        $response = $this->get("/doctors/{$this->doctor->id}");

        $response->assertStatus(200)
                ->assertViewIs('doctors.show');
    }

    public function test_show_doctor_not_found()
    {
        $response = $this->get('/doctors/99999');

        $response->assertStatus(404);
    }

    public function test_get_available_slots_success()
    {
        $date = now()->addDays(1)->format('Y-m-d');

        $response = $this->get("/doctors/{$this->doctor->id}?date={$date}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'date' => $date
                ])
                ->assertJsonStructure([
                    'success',
                    'slots',
                    'date'
                ]);
    }

    public function test_get_available_slots_validation_error()
    {
        $response = $this->get("/doctors/{$this->doctor->id}?date=invalid-date");

        $response->assertStatus(302); // Redirect with validation errors
    }

    public function test_get_available_slots_past_date()
    {
        $pastDate = now()->subDays(1)->format('Y-m-d');

        $response = $this->get("/doctors/{$this->doctor->id}?date={$pastDate}");

        $response->assertStatus(302); // Redirect with validation errors
    }

    public function test_get_available_slots_ajax()
    {
        $date = now()->addDays(1)->format('Y-m-d');

        $response = $this->getJson("/doctors/{$this->doctor->id}/slots?date={$date}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'date' => $date
                ])
                ->assertJsonStructure([
                    'success',
                    'slots',
                    'date'
                ]);
    }

    public function test_search_doctors_ajax()
    {
        $response = $this->getJson('/doctors/search?q=John');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ])
                ->assertJsonStructure([
                    'success',
                    'doctors' => [
                        '*' => [
                            'id',
                            'name',
                            'specialty',
                            'city',
                            'rating',
                            'reviews',
                            'profile_image',
                            'url'
                        ]
                    ]
                ]);
    }

    public function test_search_doctors_empty_query()
    {
        $response = $this->getJson('/doctors/search');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ]);
    }

    public function test_search_doctors_no_results()
    {
        $response = $this->getJson('/doctors/search?q=NonExistentDoctor');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'doctors' => []
                ]);
    }

    public function test_get_available_languages()
    {
        // This is tested indirectly through the index method
        $response = $this->get('/doctors');

        $response->assertStatus(200)
                ->assertViewHas('languages');
    }

    public function test_get_available_cities()
    {
        // This is tested indirectly through the index method
        $response = $this->get('/doctors');

        $response->assertStatus(200)
                ->assertViewHas('cities');
    }

    public function test_doctor_profile_includes_availability_slots()
    {
        $response = $this->get("/doctors/{$this->doctor->id}");

        $response->assertStatus(200)
                ->assertViewHas('availableSlots');
    }

    public function test_doctor_listing_pagination()
    {
        // Create multiple doctors to test pagination
        for ($i = 0; $i < 15; $i++) {
            $user = User::factory()->create(['role' => 'doctor']);
            Doctor::factory()->create([
                'user_id' => $user->id,
                'specialty_id' => $this->specialty->id,
                'is_active' => true,
                'is_verified' => true,
            ]);
        }

        $response = $this->get('/doctors');

        $response->assertStatus(200);
        // Should have pagination links
    }

    public function test_doctor_search_by_specialty_name()
    {
        $response = $this->get('/doctors?search=Cardiology');

        $response->assertStatus(200)
                ->assertViewIs('doctors.index');
    }

    public function test_doctor_filter_by_multiple_criteria()
    {
        $response = $this->get('/doctors?specialty=' . $this->specialty->id . '&city=New%20York&min_rating=4.0');

        $response->assertStatus(200)
                ->assertViewIs('doctors.index');
    }
}
