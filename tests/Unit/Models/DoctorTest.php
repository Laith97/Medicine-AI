<?php

namespace Tests\Unit\Models;

use App\Models\Doctor;
use App\Models\User;
use App\Models\Specialty;
use App\Models\AvailabilitySlot;
use App\Models\Appointment;
use App\Models\Review;
use App\Models\GoogleAccount;
use App\Models\DoctorLandingPage;
use App\Models\DoctorBlogPost;
use App\Models\ChatSession;
use App\Models\LandingPageVisit;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorTest extends TestCase
{
    use RefreshDatabase;

    protected $doctor;
    protected $user;
    protected $specialty;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'doctor']);
        $this->specialty = Specialty::factory()->create();

        $this->doctor = Doctor::factory()->create([
            'user_id' => $this->user->id,
            'specialty_id' => $this->specialty->id,
            'consultation_fee' => 10000, // $100.00
            'appointment_duration' => 30,
            'cancellation_hours' => 24,
            'is_active' => true,
            'is_verified' => true,
            'languages' => ['English', 'Spanish'],
            'appointment_type_preferences' => [
                'in_person' => true,
                'video_call' => true,
                'phone_call' => false
            ]
        ]);
    }

    public function test_doctor_can_be_created()
    {
        $this->assertInstanceOf(Doctor::class, $this->doctor);
        $this->assertEquals($this->user->id, $this->doctor->user_id);
        $this->assertEquals($this->specialty->id, $this->doctor->specialty_id);
    }

    public function test_doctor_has_fillable_attributes()
    {
        $fillable = [
            'user_id', 'specialty_id', 'license_number', 'phone', 'bio',
            'profile_image', 'languages', 'address', 'city', 'state',
            'zip_code', 'country', 'latitude', 'longitude', 'consultation_fee',
            'appointment_duration', 'auto_approve_appointments', 'allow_cancellation',
            'allow_rescheduling', 'cancellation_hours', 'average_rating',
            'total_reviews', 'is_active', 'is_verified', 'verified_at',
            'appointment_type_preferences'
        ];

        $this->assertEquals($fillable, $this->doctor->getFillable());
    }

    public function test_doctor_casts_attributes_correctly()
    {
        $this->assertIsArray($this->doctor->languages);
        $this->assertIsInt($this->doctor->consultation_fee);
        $this->assertIsInt($this->doctor->appointment_duration);
        $this->assertIsBool($this->doctor->is_active);
        $this->assertIsBool($this->doctor->is_verified);
        $this->assertIsArray($this->doctor->appointment_type_preferences);
    }

    public function test_doctor_user_relationship()
    {
        $this->assertInstanceOf(User::class, $this->doctor->user);
        $this->assertEquals($this->user->id, $this->doctor->user->id);
    }

    public function test_doctor_specialty_relationship()
    {
        $this->assertInstanceOf(Specialty::class, $this->doctor->specialty);
        $this->assertEquals($this->specialty->id, $this->doctor->specialty->id);
    }

    public function test_doctor_availability_slots_relationship()
    {
        $slot = AvailabilitySlot::factory()->create(['doctor_id' => $this->doctor->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $this->doctor->availabilitySlots());
        $this->assertTrue($this->doctor->availabilitySlots->contains($slot));
    }

    public function test_doctor_active_availability_slots_relationship()
    {
        $activeSlot = AvailabilitySlot::factory()->create([
            'doctor_id' => $this->doctor->id,
            'is_active' => true
        ]);

        $inactiveSlot = AvailabilitySlot::factory()->create([
            'doctor_id' => $this->doctor->id,
            'is_active' => false
        ]);

        $activeSlots = $this->doctor->activeAvailabilitySlots;

        $this->assertTrue($activeSlots->contains($activeSlot));
        $this->assertFalse($activeSlots->contains($inactiveSlot));
    }

    public function test_doctor_appointments_relationship()
    {
        $appointment = Appointment::factory()->create(['doctor_id' => $this->doctor->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $this->doctor->appointments());
        $this->assertTrue($this->doctor->appointments->contains($appointment));
    }

    public function test_doctor_reviews_relationship()
    {
        $review = Review::factory()->create(['doctor_id' => $this->doctor->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $this->doctor->reviews());
        $this->assertTrue($this->doctor->reviews->contains($review));
    }

    public function test_doctor_approved_reviews_relationship()
    {
        $approvedReview = Review::factory()->create([
            'doctor_id' => $this->doctor->id,
            'is_approved' => true
        ]);

        $unapprovedReview = Review::factory()->create([
            'doctor_id' => $this->doctor->id,
            'is_approved' => false
        ]);

        $approvedReviews = $this->doctor->approvedReviews;

        $this->assertTrue($approvedReviews->contains($approvedReview));
        $this->assertFalse($approvedReviews->contains($unapprovedReview));
    }

    public function test_doctor_google_account_relationship()
    {
        $googleAccount = GoogleAccount::factory()->create(['doctor_id' => $this->doctor->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $this->doctor->googleAccount());
        $this->assertEquals($googleAccount->id, $this->doctor->googleAccount->id);
    }

    public function test_doctor_landing_page_relationship()
    {
        $landingPage = DoctorLandingPage::factory()->create(['doctor_id' => $this->doctor->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $this->doctor->landingPage());
        $this->assertEquals($landingPage->id, $this->doctor->landingPage->id);
    }

    public function test_doctor_blog_posts_relationship()
    {
        $blogPost = DoctorBlogPost::factory()->create(['doctor_id' => $this->doctor->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $this->doctor->blogPosts());
        $this->assertTrue($this->doctor->blogPosts->contains($blogPost));
    }

    public function test_doctor_active_scope()
    {
        $activeDoctor = Doctor::factory()->create(['is_active' => true]);
        $inactiveDoctor = Doctor::factory()->create(['is_active' => false]);

        $activeDoctors = Doctor::active()->get();

        $this->assertTrue($activeDoctors->contains($activeDoctor));
        $this->assertFalse($activeDoctors->contains($inactiveDoctor));
    }

    public function test_doctor_verified_scope()
    {
        $verifiedDoctor = Doctor::factory()->create(['is_verified' => true]);
        $unverifiedDoctor = Doctor::factory()->create(['is_verified' => false]);

        $verifiedDoctors = Doctor::verified()->get();

        $this->assertTrue($verifiedDoctors->contains($verifiedDoctor));
        $this->assertFalse($verifiedDoctors->contains($unverifiedDoctor));
    }

    public function test_doctor_get_full_address_attribute()
    {
        $doctor = Doctor::factory()->create([
            'address' => '123 Medical St',
            'city' => 'Health City',
            'state' => 'CA',
            'zip_code' => '90210',
            'country' => 'USA'
        ]);

        $expectedAddress = '123 Medical St, Health City, CA, 90210, USA';
        $this->assertEquals($expectedAddress, $doctor->full_address);
        $this->assertEquals($expectedAddress, $doctor->getFullAddress());
    }

    public function test_doctor_get_consultation_fee_dollars_attribute()
    {
        $this->assertEquals(100.0, $this->doctor->consultation_fee_dollars);
    }

    public function test_doctor_update_rating()
    {
        Review::factory()->create([
            'doctor_id' => $this->doctor->id,
            'rating' => 5,
            'is_approved' => true
        ]);

        Review::factory()->create([
            'doctor_id' => $this->doctor->id,
            'rating' => 4,
            'is_approved' => true
        ]);

        Review::factory()->create([
            'doctor_id' => $this->doctor->id,
            'rating' => 3,
            'is_approved' => false // Should not be counted
        ]);

        $this->doctor->updateRating();

        $this->assertEquals(2, $this->doctor->total_reviews);
        $this->assertEquals(4.5, $this->doctor->average_rating);
    }

    public function test_doctor_can_cancel_within_hours()
    {
        $futureAppointment = now()->addDays(2);
        $nearAppointment = now()->addHours(12);

        $this->assertTrue($this->doctor->canCancelWithinHours($futureAppointment));
        $this->assertFalse($this->doctor->canCancelWithinHours($nearAppointment));

        // Test when cancellation is not allowed
        $this->doctor->allow_cancellation = false;
        $this->doctor->save();

        $this->assertFalse($this->doctor->canCancelWithinHours($futureAppointment));
    }

    public function test_doctor_get_enabled_appointment_types()
    {
        $enabledTypes = $this->doctor->getEnabledAppointmentTypes();

        $this->assertContains('in_person', $enabledTypes);
        $this->assertContains('video_call', $enabledTypes);
        $this->assertNotContains('phone_call', $enabledTypes);
    }

    public function test_doctor_is_appointment_type_enabled()
    {
        $this->assertTrue($this->doctor->isAppointmentTypeEnabled('in_person'));
        $this->assertTrue($this->doctor->isAppointmentTypeEnabled('video_call'));
        $this->assertFalse($this->doctor->isAppointmentTypeEnabled('phone_call'));
    }

    public function test_doctor_get_appointment_type_preferences()
    {
        $preferences = $this->doctor->getAppointmentTypePreferences();

        $this->assertTrue($preferences['in_person']);
        $this->assertTrue($preferences['video_call']);
        $this->assertFalse($preferences['phone_call']);
    }

    public function test_doctor_update_appointment_type_preferences()
    {
        $newPreferences = [
            'in_person' => false,
            'video_call' => true,
            'phone_call' => true
        ];

        $this->doctor->updateAppointmentTypePreferences($newPreferences);

        $this->assertEquals($newPreferences, $this->doctor->appointment_type_preferences);
    }

    public function test_doctor_get_available_slots()
    {
        $date = now()->addDay()->format('Y-m-d');
        $dayOfWeek = strtolower(Carbon::parse($date)->format('l'));

        AvailabilitySlot::factory()->create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => $dayOfWeek,
            'start_time' => '09:00',
            'end_time' => '11:00',
            'slot_duration' => 30,
            'max_bookings_per_slot' => 1,
            'is_active' => true,
            'effective_from' => null,
            'effective_until' => null,
        ]);

        $slots = $this->doctor->getAvailableSlots($date);

        $this->assertCount(4, $slots); // 2 hours / 30 minutes = 4 slots
        $this->assertEquals('09:00', $slots[0]['start_time']);
        $this->assertEquals('09:30', $slots[0]['end_time']);
    }

    public function test_doctor_default_appointment_type_preferences()
    {
        // Create a doctor without specifying appointment_type_preferences
        // This should use the database default
        $doctor = Doctor::factory()->create();

        // Manually set to null to test the method's null handling
        $doctor->appointment_type_preferences = null;

        $preferences = $doctor->getAppointmentTypePreferences();

        $this->assertTrue($preferences['in_person']);
        $this->assertFalse($preferences['video_call']);
        $this->assertFalse($preferences['phone_call']);
    }
}
