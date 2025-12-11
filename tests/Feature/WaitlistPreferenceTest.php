<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Doctor;
use App\Models\WaitlistPatientPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaitlistPreferenceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->doctor = Doctor::factory()->create();
    }

    /** @test */
    public function user_can_create_waitlist_preferences()
    {
        $preferenceData = [
            'doctor_id' => $this->doctor->id,
            'preferred_times' => ['morning', 'afternoon'],
            'preferred_days' => ['monday', 'wednesday', 'friday'],
            'service_priorities' => ['consultation' => 'high', 'follow_up' => 'medium'],
            'notification_settings' => [
                'email' => true,
                'sms' => false,
                'push' => true,
            ],
            'auto_accept_threshold' => 7,
            'max_travel_distance' => 25.5,
            'preferred_location_lat' => 40.7128,
            'preferred_location_lng' => -74.0060,
            'emergency_contact' => 'John Doe - 555-0123',
            'special_requirements' => 'Wheelchair accessible location required',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/waitlist/preferences', $preferenceData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'preference' => [
                    'id',
                    'patient_id',
                    'doctor_id',
                    'preferred_times',
                    'preferred_days',
                    'service_priorities',
                    'notification_settings',
                    'auto_accept_threshold',
                    'max_travel_distance',
                    'preferred_location_lat',
                    'preferred_location_lng',
                    'emergency_contact',
                    'special_requirements',
                ]
            ]);

        $this->assertDatabaseHas('waitlist_patient_preferences', [
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
            'auto_accept_threshold' => 7,
            'max_travel_distance' => 25.5,
        ]);
    }

    /** @test */
    public function user_can_retrieve_their_preferences()
    {
        WaitlistPatientPreference::factory()->create([
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
            'preferred_times' => ['morning'],
            'preferred_days' => ['monday', 'tuesday'],
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/waitlist/preferences');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'preferences',
                'suggested'
            ]);

        $this->assertCount(1, $response->json('preferences'));
    }

    /** @test */
    public function user_can_update_preferences()
    {
        $preference = WaitlistPatientPreference::factory()->create([
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
            'preferred_times' => ['morning'],
        ]);

        $updateData = [
            'preferred_times' => ['afternoon', 'evening'],
            'auto_accept_threshold' => 14,
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/waitlist/preferences/{$preference->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'preference'
            ]);

        $this->assertDatabaseHas('waitlist_patient_preferences', [
            'id' => $preference->id,
            'auto_accept_threshold' => 14,
        ]);
    }

    /** @test */
    public function user_cannot_update_other_users_preferences()
    {
        $otherUser = User::factory()->create();
        $preference = WaitlistPatientPreference::factory()->create([
            'patient_id' => $otherUser->id,
            'doctor_id' => $this->doctor->id,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/waitlist/preferences/{$preference->id}", [
                'preferred_times' => ['morning'],
            ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function validation_fails_for_invalid_preference_data()
    {
        $invalidData = [
            'doctor_id' => 99999, // Non-existent doctor
            'preferred_times' => ['invalid_time'],
            'preferred_days' => ['invalid_day'],
            'auto_accept_threshold' => 100, // Too high
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/waitlist/preferences', $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'doctor_id',
                'preferred_times.0',
                'preferred_days.0',
                'auto_accept_threshold',
            ]);
    }

    /** @test */
    public function user_can_get_preference_analytics()
    {
        // Create multiple preferences for the user
        WaitlistPatientPreference::factory()->count(3)->create([
            'patient_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/waitlist/preferences/analytics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'analytics' => [
                    'total_preferences',
                    'doctors_with_preferences',
                    'most_common_time_preferences',
                    'most_common_day_preferences',
                    'average_auto_accept_threshold',
                ]
            ]);
    }
}
