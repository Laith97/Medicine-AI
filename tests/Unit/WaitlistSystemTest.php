<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Doctor;

use App\Models\WaitlistPatientPreference;
use App\Models\WaitlistEntry;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class WaitlistSystemTest extends TestCase
{
    use RefreshDatabase;

    protected $patient;
    protected $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a patient user
        $this->patient = User::factory()->create([
            'role' => 'patient',
            'name' => 'Test Patient',
            'email' => 'patient@example.com',
        ]);

        // Create a doctor user
        $this->doctor = User::factory()->create([
            'role' => 'doctor',
            'email' => 'doctor@example.com',
        ]);

        Doctor::factory()->create([
            'user_id' => $this->doctor->id,
            'name' => 'Dr. Test',
        ]);
    }

    public function test_waitlist_preference_can_be_created()
    {
        $preferenceData = [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'preference_level' => 1,
            'notes' => 'Preferred morning appointments',
            'active' => true,
        ];

        $preference = WaitlistPatientPreference::create($preferenceData);

        $this->assertDatabaseHas('waitlist_patient_preferences', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'preference_level' => 1,
            'active' => true,
        ]);
    }

    public function test_waitlist_preference_can_be_updated()
    {
        $preference = WaitlistPatientPreference::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'preference_level' => 1,
            'notes' => 'Old notes',
            'active' => true,
        ]);

        $preference->update([
            'preference_level' => 2,
            'notes' => 'New notes',
            'active' => false,
        ]);

        $this->assertDatabaseHas('waitlist_patient_preferences', [
            'id' => $preference->id,
            'preference_level' => 2,
            'notes' => 'New notes',
            'active' => false,
        ]);
    }

    public function test_waitlist_preference_can_be_deleted()
    {
        $preference = WaitlistPatientPreference::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'preference_level' => 1,
            'active' => true,
        ]);

        $preference->delete();

        $this->assertDatabaseMissing('waitlist_patient_preferences', [
            'id' => $preference->id,
        ]);
    }

    public function test_waitlist_entry_can_be_created()
    {
        $entryData = [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'priority' => 3,
            'reason' => 'Regular checkup',
            'status' => 'pending',
            'estimated_wait_days' => 14,
        ];

        $entry = WaitlistEntry::create($entryData);

        $this->assertDatabaseHas('waitlist_entries', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'priority' => 3,
            'status' => 'pending',
        ]);
    }

    public function test_waitlist_entry_priority_assignment()
    {
        $highPriorityEntry = WaitlistEntry::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'priority' => 1, // Highest priority
            'reason' => 'Emergency',
            'status' => 'pending',
            'estimated_wait_days' => 1,
        ]);

        $lowPriorityEntry = WaitlistEntry::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'priority' => 5, // Lower priority
            'reason' => 'Routine checkup',
            'status' => 'pending',
            'estimated_wait_days' => 30,
        ]);

        // Test ordering by priority
        $sortedEntries = WaitlistEntry::orderBy('priority')->get();
        
        $this->assertEquals(1, $sortedEntries[0]->priority);
        $this->assertEquals(5, $sortedEntries[1]->priority);
    }

    public function test_waitlist_entry_status_transition()
    {
        $entry = WaitlistEntry::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'priority' => 3,
            'reason' => 'Regular checkup',
            'status' => 'pending',
        ]);

        // Test status transitions
        $entry->update(['status' => 'confirmed']);
        $this->assertEquals('confirmed', $entry->fresh()->status);

        $entry->update(['status' => 'scheduled']);
        $this->assertEquals('scheduled', $entry->fresh()->status);

        $entry->update(['status' => 'completed']);
        $this->assertEquals('completed', $entry->fresh()->status);

        $entry->update(['status' => 'cancelled']);
        $this->assertEquals('cancelled', $entry->fresh()->status);
    }

    public function test_waitlist_preference_matching()
    {
        $preference = WaitlistPatientPreference::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'preference_level' => 1,
            'notes' => 'Prefers morning appointments',
            'active' => true,
        ]);

        $entry = WaitlistEntry::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'priority' => 2,
            'reason' => 'Follow-up visit',
            'status' => 'pending',
        ]);

        // Check if the entry matches the preference
        $this->assertEquals($this->patient->id, $entry->patient_id);
        $this->assertEquals($this->doctor->id, $entry->doctor_id);

        $matchingPreferences = WaitlistPatientPreference::where('patient_id', $this->patient->id)
            ->where('doctor_id', $this->doctor->id)
            ->active()
            ->get();

        $this->assertCount(1, $matchingPreferences);
        $this->assertTrue($matchingPreferences[0]->active);
    }

    public function test_waitlist_entry_estimated_wait_calculation()
    {
        $entry = WaitlistEntry::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'priority' => 3,
            'reason' => 'Regular checkup',
            'status' => 'pending',
            'estimated_wait_days' => 21,
        ]);

        // Should be able to access the estimated wait days
        $this->assertGreaterThanOrEqual(0, $entry->estimated_wait_days);
        $this->assertEquals(21, $entry->estimated_wait_days);
    }
}