<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\Doctor;
use App\Models\Waitlist;
use App\Models\WaitlistEntry;
use App\Models\WaitlistPatientPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaitlistTest extends TestCase
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
        $waitlist = Waitlist::factory()->create([
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
        ]);

        $this->assertInstanceOf(User::class, $waitlist->patient);
        $this->assertEquals($this->user->id, $waitlist->patient->id);
    }

    /** @test */
    public function it_belongs_to_doctor()
    {
        $waitlist = Waitlist::factory()->create([
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
        ]);

        $this->assertInstanceOf(Doctor::class, $waitlist->doctor);
        $this->assertEquals($this->doctor->id, $waitlist->doctor->id);
    }

    /** @test */
    public function it_has_many_entries()
    {
        $waitlist = Waitlist::factory()->create([
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
        ]);

        WaitlistEntry::factory()->count(3)->create(['waitlist_id' => $waitlist->id]);

        $this->assertCount(3, $waitlist->entries);
        $this->assertInstanceOf(WaitlistEntry::class, $waitlist->entries->first());
    }

    /** @test */
    public function it_has_many_patient_preferences()
    {
        $waitlist = Waitlist::factory()->create([
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
        ]);

        $secondDoctor = \App\Models\Doctor::factory()->create();

        WaitlistPatientPreference::factory()->create([
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
        ]);

        WaitlistPatientPreference::factory()->create([
            'patient_id' => $this->user->id,
            'doctor_id' => $secondDoctor->id,
        ]);

        $this->assertCount(2, $waitlist->patientPreferences);
        $this->assertInstanceOf(WaitlistPatientPreference::class, $waitlist->patientPreferences->first());
    }

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        $waitlist = new Waitlist();

        $fillable = [
            'patient_id',
            'doctor_id',
            'service_type',
            'priority_level',
            'preferred_time_slots',
            'preferred_days',
            'max_wait_days',
            'notification_channels',
            'status',
        ];

        $this->assertEquals($fillable, $waitlist->getFillable());
    }

    /** @test */
    public function it_has_correct_casts()
    {
        $waitlist = new Waitlist();

        $expectedCasts = [
            'id' => 'int',
            'preferred_time_slots' => 'array',
            'preferred_days' => 'array',
            'notification_channels' => 'array',
            'max_wait_days' => 'integer',
        ];

        $this->assertEquals($expectedCasts, $waitlist->getCasts());
    }

    /** @test */
    public function it_can_scope_active_waitlists()
    {
        Waitlist::factory()->count(3)->create(['status' => 'active']);
        Waitlist::factory()->count(2)->create(['status' => 'cancelled']);

        $activeWaitlists = Waitlist::active()->get();

        $this->assertCount(3, $activeWaitlists);
        $activeWaitlists->each(function ($waitlist) {
            $this->assertEquals('active', $waitlist->status);
        });
    }

    /** @test */
    public function it_can_scope_waitlists_for_doctor()
    {
        $doctor2 = Doctor::factory()->create();

        Waitlist::factory()->count(2)->create(['doctor_id' => $this->doctor->id]);
        Waitlist::factory()->count(3)->create(['doctor_id' => $doctor2->id]);

        $doctorWaitlists = Waitlist::forDoctor($this->doctor->id)->get();

        $this->assertCount(2, $doctorWaitlists);
        $doctorWaitlists->each(function ($waitlist) {
            $this->assertEquals($this->doctor->id, $waitlist->doctor_id);
        });
    }

    /** @test */
    public function it_can_scope_waitlists_for_patient()
    {
        $user2 = User::factory()->create();

        Waitlist::factory()->count(2)->create(['patient_id' => $this->user->id]);
        Waitlist::factory()->count(3)->create(['patient_id' => $user2->id]);

        $patientWaitlists = Waitlist::forPatient($this->user->id)->get();

        $this->assertCount(2, $patientWaitlists);
        $patientWaitlists->each(function ($waitlist) {
            $this->assertEquals($this->user->id, $waitlist->patient_id);
        });
    }

    /** @test */
    public function it_can_check_if_active()
    {
        $activeWaitlist = Waitlist::factory()->create(['status' => 'active']);
        $pausedWaitlist = Waitlist::factory()->create(['status' => 'paused']);
        $cancelledWaitlist = Waitlist::factory()->create(['status' => 'cancelled']);

        $this->assertTrue($activeWaitlist->isActive());
        $this->assertFalse($pausedWaitlist->isActive());
        $this->assertFalse($cancelledWaitlist->isActive());
    }

    /** @test */
    public function it_can_check_if_paused()
    {
        $activeWaitlist = Waitlist::factory()->create(['status' => 'active']);
        $pausedWaitlist = Waitlist::factory()->create(['status' => 'paused']);

        $this->assertFalse($activeWaitlist->isPaused());
        $this->assertTrue($pausedWaitlist->isPaused());
    }

    /** @test */
    public function it_can_check_if_cancelled()
    {
        $activeWaitlist = Waitlist::factory()->create(['status' => 'active']);
        $cancelledWaitlist = Waitlist::factory()->create(['status' => 'cancelled']);

        $this->assertFalse($activeWaitlist->isCancelled());
        $this->assertTrue($cancelledWaitlist->isCancelled());
    }

    /** @test */
    public function it_can_check_if_fulfilled()
    {
        $activeWaitlist = Waitlist::factory()->create(['status' => 'active']);
        $fulfilledWaitlist = Waitlist::factory()->create(['status' => 'fulfilled']);

        $this->assertFalse($activeWaitlist->isFulfilled());
        $this->assertTrue($fulfilledWaitlist->isFulfilled());
    }

    /** @test */
    public function it_can_pause_waitlist()
    {
        $waitlist = Waitlist::factory()->create(['status' => 'active']);

        $waitlist->pause();

        $this->assertEquals('paused', $waitlist->fresh()->status);
    }

    /** @test */
    public function it_can_resume_waitlist()
    {
        $waitlist = Waitlist::factory()->create(['status' => 'paused']);

        $waitlist->resume();

        $this->assertEquals('active', $waitlist->fresh()->status);
    }

    /** @test */
    public function it_can_cancel_waitlist()
    {
        $waitlist = Waitlist::factory()->create(['status' => 'active']);

        $waitlist->cancel();

        $this->assertEquals('cancelled', $waitlist->fresh()->status);
    }

    /** @test */
    public function it_can_fulfill_waitlist()
    {
        $waitlist = Waitlist::factory()->create(['status' => 'active']);

        $waitlist->fulfill();

        $this->assertEquals('fulfilled', $waitlist->fresh()->status);
    }

    /** @test */
    public function it_handles_factory_creation()
    {
        $waitlist = Waitlist::factory()->create();

        $this->assertInstanceOf(Waitlist::class, $waitlist);
        $this->assertNotNull($waitlist->patient_id);
        $this->assertNotNull($waitlist->doctor_id);
        $this->assertNotNull($waitlist->service_type);
        $this->assertNotNull($waitlist->priority_level);
        $this->assertNotNull($waitlist->status);
    }
}
