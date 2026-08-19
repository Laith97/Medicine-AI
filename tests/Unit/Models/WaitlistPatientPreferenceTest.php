<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\Doctor;
use App\Models\WaitlistPatientPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaitlistPatientPreferenceTest extends TestCase
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
    public function it_belongs_to_patient()
    {
        $preference = WaitlistPatientPreference::factory()->create([
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
        ]);

        $this->assertInstanceOf(User::class, $preference->patient);
        $this->assertEquals($this->user->id, $preference->patient->id);
    }

    /** @test */
    public function it_belongs_to_doctor()
    {
        $preference = WaitlistPatientPreference::factory()->create([
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
        ]);

        $this->assertInstanceOf(Doctor::class, $preference->doctor);
        $this->assertEquals($this->doctor->id, $preference->doctor->id);
    }

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        $preference = new WaitlistPatientPreference();

        $fillable = [
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
        ];

        $this->assertEquals($fillable, $preference->getFillable());
    }

    /** @test */
    public function it_has_correct_casts()
    {
        $preference = new WaitlistPatientPreference();

        $expectedCasts = [
            'id' => 'int',
            'preferred_times' => 'array',
            'preferred_days' => 'array',
            'service_priorities' => 'array',
            'notification_settings' => 'array',
            'auto_accept_threshold' => 'integer',
            'max_travel_distance' => 'decimal:2',
            'preferred_location_lat' => 'decimal:8',
            'preferred_location_lng' => 'decimal:8',
        ];

        $this->assertEquals($expectedCasts, $preference->getCasts());
    }

    /** @test */
    public function it_can_scope_preferences_for_patient()
    {
        WaitlistPatientPreference::factory()->count(2)->create(['patient_id' => $this->user->id]);
        WaitlistPatientPreference::factory()->count(3)->create(); // Different patient

        $patientPreferences = WaitlistPatientPreference::forPatient($this->user->id)->get();

        $this->assertCount(2, $patientPreferences);
        $patientPreferences->each(function ($preference) {
            $this->assertEquals($this->user->id, $preference->patient_id);
        });
    }

    /** @test */
    public function it_can_scope_preferences_for_doctor()
    {
        WaitlistPatientPreference::factory()->count(2)->create(['doctor_id' => $this->doctor->id]);
        WaitlistPatientPreference::factory()->count(3)->create(); // Different doctor

        $doctorPreferences = WaitlistPatientPreference::forDoctor($this->doctor->id)->get();

        $this->assertCount(2, $doctorPreferences);
        $doctorPreferences->each(function ($preference) {
            $this->assertEquals($this->doctor->id, $preference->doctor_id);
        });
    }

    /** @test */
    public function it_can_check_email_notifications_enabled()
    {
        $preferenceWithEmail = WaitlistPatientPreference::factory()->create([
            'notification_settings' => ['email' => true, 'sms' => false],
        ]);

        $preferenceWithoutEmail = WaitlistPatientPreference::factory()->create([
            'notification_settings' => ['email' => false, 'sms' => true],
        ]);

        $this->assertTrue($preferenceWithEmail->hasEmailNotifications());
        $this->assertFalse($preferenceWithoutEmail->hasEmailNotifications());
    }

    /** @test */
    public function it_can_check_sms_notifications_enabled()
    {
        $preferenceWithSms = WaitlistPatientPreference::factory()->create([
            'notification_settings' => ['email' => false, 'sms' => true],
        ]);

        $preferenceWithoutSms = WaitlistPatientPreference::factory()->create([
            'notification_settings' => ['email' => true, 'sms' => false],
        ]);

        $this->assertTrue($preferenceWithSms->hasSmsNotifications());
        $this->assertFalse($preferenceWithoutSms->hasSmsNotifications());
    }

    /** @test */
    public function it_can_check_push_notifications_enabled()
    {
        $preferenceWithPush = WaitlistPatientPreference::factory()->create([
            'notification_settings' => ['push' => true, 'email' => false],
        ]);

        $preferenceWithoutPush = WaitlistPatientPreference::factory()->create([
            'notification_settings' => ['push' => false, 'email' => true],
        ]);

        $this->assertTrue($preferenceWithPush->hasPushNotifications());
        $this->assertFalse($preferenceWithoutPush->hasPushNotifications());
    }

    /** @test */
    public function it_can_get_service_priority()
    {
        $preference = WaitlistPatientPreference::factory()->create([
            'service_priorities' => [
                'consultation' => 'high',
                'follow_up' => 'medium',
                'emergency' => 'urgent',
            ],
        ]);

        $this->assertEquals('high', $preference->getServicePriority('consultation'));
        $this->assertEquals('medium', $preference->getServicePriority('follow_up'));
        $this->assertEquals('urgent', $preference->getServicePriority('emergency'));
        $this->assertEquals('medium', $preference->getServicePriority('unknown_service'));
    }

    /** @test */
    public function it_can_check_preferred_time_matches()
    {
        $morningPreference = WaitlistPatientPreference::factory()->create([
            'preferred_times' => ['morning'],
        ]);

        $afternoonPreference = WaitlistPatientPreference::factory()->create([
            'preferred_times' => ['afternoon'],
        ]);

        $noTimePreference = WaitlistPatientPreference::factory()->create([
            'preferred_times' => [],
        ]);

        // Morning slot
        $this->assertTrue($morningPreference->matchesPreferredTime('09:00:00'));
        $this->assertFalse($afternoonPreference->matchesPreferredTime('09:00:00'));
        $this->assertTrue($noTimePreference->matchesPreferredTime('09:00:00'));

        // Afternoon slot
        $this->assertFalse($morningPreference->matchesPreferredTime('14:00:00'));
        $this->assertTrue($afternoonPreference->matchesPreferredTime('14:00:00'));
        $this->assertTrue($noTimePreference->matchesPreferredTime('14:00:00'));

        // Evening slot
        $this->assertFalse($morningPreference->matchesPreferredTime('19:00:00'));
        $this->assertFalse($afternoonPreference->matchesPreferredTime('19:00:00'));
        $this->assertTrue($noTimePreference->matchesPreferredTime('19:00:00'));
    }

    /** @test */
    public function it_can_check_preferred_day_matches()
    {
        $weekdayPreference = WaitlistPatientPreference::factory()->create([
            'preferred_days' => ['monday', 'wednesday', 'friday'],
        ]);

        $weekendPreference = WaitlistPatientPreference::factory()->create([
            'preferred_days' => ['saturday', 'sunday'],
        ]);

        $noDayPreference = WaitlistPatientPreference::factory()->create([
            'preferred_days' => [],
        ]);

        // Monday (preferred weekday)
        $this->assertTrue($weekdayPreference->matchesPreferredDay('monday'));
        $this->assertFalse($weekendPreference->matchesPreferredDay('monday'));
        $this->assertTrue($noDayPreference->matchesPreferredDay('monday'));

        // Saturday (preferred weekend)
        $this->assertFalse($weekdayPreference->matchesPreferredDay('saturday'));
        $this->assertTrue($weekendPreference->matchesPreferredDay('saturday'));
        $this->assertTrue($noDayPreference->matchesPreferredDay('saturday'));
    }

    /** @test */
    public function it_can_check_auto_accept_threshold()
    {
        $preference = WaitlistPatientPreference::factory()->create([
            'auto_accept_threshold' => 7, // 7 days
        ]);

        $this->assertTrue($preference->shouldAutoAccept(5)); // Within threshold
        $this->assertTrue($preference->shouldAutoAccept(7)); // At threshold
        $this->assertFalse($preference->shouldAutoAccept(10)); // Beyond threshold
    }

    /** @test */
    public function it_handles_factory_creation()
    {
        $preference = WaitlistPatientPreference::factory()->create();

        $this->assertInstanceOf(WaitlistPatientPreference::class, $preference);
        $this->assertNotNull($preference->patient_id);
        $this->assertNotNull($preference->doctor_id);
        $this->assertIsArray($preference->preferred_times);
        $this->assertIsArray($preference->preferred_days);
        $this->assertIsArray($preference->service_priorities);
        $this->assertIsArray($preference->notification_settings);
    }

    /** @test */
    public function it_handles_edge_cases_with_empty_arrays()
    {
        $preference = WaitlistPatientPreference::factory()->create([
            'preferred_times' => [],
            'preferred_days' => [],
            'service_priorities' => [],
            'notification_settings' => [],
        ]);

        // Should not throw errors
        $this->assertTrue($preference->matchesPreferredTime('09:00:00'));
        $this->assertTrue($preference->matchesPreferredDay('monday'));
        $this->assertEquals('medium', $preference->getServicePriority('any_service'));
        $this->assertFalse($preference->hasEmailNotifications());
        $this->assertFalse($preference->hasSmsNotifications());
        $this->assertFalse($preference->hasPushNotifications());
    }

    /** @test */
    public function it_handles_null_notification_settings()
    {
        $preference = WaitlistPatientPreference::factory()->create([
            'notification_settings' => null,
        ]);

        $this->assertFalse($preference->hasEmailNotifications());
        $this->assertFalse($preference->hasSmsNotifications());
        $this->assertFalse($preference->hasPushNotifications());
    }
}
