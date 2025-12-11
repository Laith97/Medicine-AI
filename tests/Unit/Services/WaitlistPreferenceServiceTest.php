<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\Doctor;
use App\Models\WaitlistPatientPreference;
use App\Models\Appointment;
use App\Services\WaitlistPreferenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class WaitlistPreferenceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WaitlistPreferenceService $preferenceService;
    protected User $user;
    protected Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->preferenceService = app(WaitlistPreferenceService::class);
        $this->user = User::factory()->create();
        $this->doctor = Doctor::factory()->create([
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);
    }

    /** @test */
    public function it_calculates_matching_score_for_slot_with_preferences()
    {
        $preferences = WaitlistPatientPreference::factory()->create([
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
            'preferred_times' => ['morning'],
            'preferred_days' => ['monday'],
            'preferred_location_lat' => 40.7128,
            'preferred_location_lng' => -74.0060,
            'max_travel_distance' => 50,
        ]);

        $slot = [
            'date' => '2025-11-18', // Monday
            'time' => '09:00:00', // Morning
        ];

        $score = $this->preferenceService->calculateMatchingScore($slot, $preferences, $this->doctor->id);

        $this->assertGreaterThan(80, $score); // Should be high score for perfect match
        $this->assertLessThanOrEqual(100, $score);
    }

    /** @test */
    public function it_calculates_time_preference_score()
    {
        $preferences = WaitlistPatientPreference::factory()->create([
            'preferred_times' => ['morning'],
        ]);

        // Perfect match
        $score = $this->invokePrivateMethod($this->preferenceService, 'calculateTimePreferenceScore', ['09:00:00', $preferences]);
        $this->assertEquals(100, $score);

        // Close match
        $score = $this->invokePrivateMethod($this->preferenceService, 'calculateTimePreferenceScore', ['07:00:00', $preferences]);
        $this->assertEquals(70, $score);

        // Poor match
        $score = $this->invokePrivateMethod($this->preferenceService, 'calculateTimePreferenceScore', ['19:00:00', $preferences]);
        $this->assertEquals(30, $score);

        // No preferences
        $preferencesNoTime = WaitlistPatientPreference::factory()->create([
            'preferred_times' => [],
        ]);
        $score = $this->invokePrivateMethod($this->preferenceService, 'calculateTimePreferenceScore', ['19:00:00', $preferencesNoTime]);
        $this->assertEquals(100, $score);
    }

    /** @test */
    public function it_calculates_day_preference_score()
    {
        $preferences = WaitlistPatientPreference::factory()->create([
            'preferred_days' => ['monday', 'wednesday'],
        ]);

        // Perfect match
        $score = $this->invokePrivateMethod($this->preferenceService, 'calculateDayPreferenceScore', ['2025-11-18', $preferences]); // Monday
        $this->assertEquals(100, $score);

        // Weekend preference match
        $weekendPreferences = WaitlistPatientPreference::factory()->create([
            'preferred_days' => ['saturday', 'sunday'],
        ]);
        $score = $this->invokePrivateMethod($this->preferenceService, 'calculateDayPreferenceScore', ['2025-11-16', $weekendPreferences]); // Saturday
        $this->assertEquals(80, $score);

        // No preferences
        $preferencesNoDays = WaitlistPatientPreference::factory()->create([
            'preferred_days' => [],
        ]);
        $score = $this->invokePrivateMethod($this->preferenceService, 'calculateDayPreferenceScore', ['2025-11-18', $preferencesNoDays]);
        $this->assertEquals(100, $score);
    }

    /** @test */
    public function it_calculates_geographic_proximity_score()
    {
        $preferences = WaitlistPatientPreference::factory()->create([
            'preferred_location_lat' => 40.7128,
            'preferred_location_lng' => -74.0060,
            'max_travel_distance' => 50,
        ]);

        $slot = ['date' => '2025-11-18', 'time' => '09:00:00'];

        // Same location (perfect score)
        $score = $this->invokePrivateMethod($this->preferenceService, 'calculateGeographicProximityScore', [$slot, $preferences, $this->doctor->id]);
        $this->assertGreaterThan(90, $score);

        // No location data
        $doctorNoLocation = Doctor::factory()->create();
        $score = $this->invokePrivateMethod($this->preferenceService, 'calculateGeographicProximityScore', [$slot, $preferences, $doctorNoLocation->id]);
        $this->assertEquals(50, $score);

        // No preferred location
        $preferencesNoLocation = WaitlistPatientPreference::factory()->create();
        $score = $this->invokePrivateMethod($this->preferenceService, 'calculateGeographicProximityScore', [$slot, $preferencesNoLocation, $this->doctor->id]);
        $this->assertEquals(50, $score);
    }

    /** @test */
    public function it_calculates_wait_time_optimization_score()
    {
        $preferences = WaitlistPatientPreference::factory()->create();

        // Very soon (within 7 days)
        $slotSoon = ['date' => now()->addDays(3)->toDateString(), 'time' => '09:00:00'];
        $score = $this->invokePrivateMethod($this->preferenceService, 'calculateWaitTimeOptimizationScore', [$slotSoon, $preferences]);
        $this->assertEquals(100, $score);

        // Medium term (within 14 days)
        $slotMedium = ['date' => now()->addDays(10)->toDateString(), 'time' => '09:00:00'];
        $score = $this->invokePrivateMethod($this->preferenceService, 'calculateWaitTimeOptimizationScore', [$slotMedium, $preferences]);
        $this->assertEquals(80, $score);

        // Long term (more than 30 days)
        $slotLong = ['date' => now()->addDays(60)->toDateString(), 'time' => '09:00:00'];
        $score = $this->invokePrivateMethod($this->preferenceService, 'calculateWaitTimeOptimizationScore', [$slotLong, $preferences]);
        $this->assertEquals(30, $score);
    }

    /** @test */
    public function it_gets_matching_recommendations_for_patient()
    {
        $preferences = WaitlistPatientPreference::factory()->create([
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
            'preferred_times' => ['morning'],
            'preferred_days' => ['monday'],
        ]);

        // Create availability slot that matches preferences
        $availabilitySlot = \App\Models\AvailabilitySlot::factory()->create([
            'doctor_id' => $this->doctor->id,
            'date' => now()->addDays(7)->next('monday')->toDateString(),
            'start_time' => '09:00:00',
            'is_available' => true,
        ]);

        $recommendations = $this->preferenceService->getMatchingRecommendations($this->user->id, $this->doctor->id);

        $this->assertNotEmpty($recommendations);
        $this->assertGreaterThan(60, $recommendations[0]['matching_score']);
        $this->assertArrayHasKey('slot', $recommendations[0]);
        $this->assertArrayHasKey('match_reasons', $recommendations[0]);
    }

    /** @test */
    public function it_returns_empty_recommendations_when_no_preferences()
    {
        $recommendations = $this->preferenceService->getMatchingRecommendations($this->user->id, $this->doctor->id);

        $this->assertEmpty($recommendations);
    }

    /** @test */
    public function it_gets_suggested_preferences_from_booking_history()
    {
        // Create appointments with consistent patterns
        Appointment::factory()->count(5)->create([
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => Carbon::parse('2025-01-01 09:00:00'), // Monday morning
            'status' => 'completed',
        ]);

        Appointment::factory()->count(3)->create([
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => Carbon::parse('2025-01-03 14:00:00'), // Wednesday afternoon
            'status' => 'completed',
        ]);

        $suggestions = $this->preferenceService->getSuggestedPreferences($this->user->id);

        $this->assertArrayHasKey('preferred_times', $suggestions);
        $this->assertArrayHasKey('preferred_days', $suggestions);
        $this->assertArrayHasKey('preferred_doctors', $suggestions);

        $this->assertContains('morning', $suggestions['preferred_times']);
        $this->assertContains('monday', $suggestions['preferred_days']);
    }

    /** @test */
    public function it_returns_empty_suggestions_for_new_patients()
    {
        $suggestions = $this->preferenceService->getSuggestedPreferences($this->user->id);

        $this->assertEmpty($suggestions);
    }

    /** @test */
    public function it_gets_preference_analytics()
    {
        // Create multiple preferences
        WaitlistPatientPreference::factory()->count(3)->create([
            'patient_id' => $this->user->id,
        ]);

        $analytics = $this->preferenceService->getPreferenceAnalytics($this->user->id);

        $this->assertEquals(3, $analytics['total_preferences']);
        $this->assertArrayHasKey('doctors_with_preferences', $analytics);
        $this->assertArrayHasKey('most_common_time_preferences', $analytics);
        $this->assertArrayHasKey('most_common_day_preferences', $analytics);
        $this->assertArrayHasKey('average_auto_accept_threshold', $analytics);
    }

    /** @test */
    public function it_calculates_distance_using_haversine_formula()
    {
        // Test distance calculation between two known points
        $distance = $this->invokePrivateMethod($this->preferenceService, 'calculateDistance', [40.7128, -74.0060, 34.0522, -118.2437]);

        // Distance between NYC and LA should be approximately 3935 km
        $this->assertGreaterThan(3900, $distance);
        $this->assertLessThan(4000, $distance);
    }

    /** @test */
    public function it_handles_edge_cases_in_matching_score_calculation()
    {
        $preferences = WaitlistPatientPreference::factory()->create([
            'preferred_times' => [],
            'preferred_days' => [],
        ]);

        $slot = ['date' => '2025-11-18', 'time' => '09:00:00'];

        $score = $this->preferenceService->calculateMatchingScore($slot, $preferences, $this->doctor->id);

        // Should still return a valid score even with empty preferences
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    /** @test */
    public function it_updates_learning_data_when_preferences_change()
    {
        $preferenceData = [
            'preferred_times' => ['morning'],
            'preferred_days' => ['monday', 'wednesday'],
            'auto_accept_threshold' => 7,
        ];

        // This should not throw an exception
        $this->preferenceService->updateLearningData($this->user->id, $preferenceData);

        // The method logs data, so we can't easily test the logging output
        // but we can ensure it doesn't throw exceptions
        $this->assertTrue(true);
    }

    /**
     * Helper method to invoke private methods for testing
     */
    private function invokePrivateMethod($object, $method, $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
