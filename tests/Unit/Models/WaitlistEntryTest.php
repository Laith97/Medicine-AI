<?php

namespace Tests\Unit\Models;

use App\Models\Waitlist;
use App\Models\WaitlistEntry;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class WaitlistEntryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_belongs_to_waitlist()
    {
        $waitlist = Waitlist::factory()->create();
        $entry = WaitlistEntry::factory()->create(['waitlist_id' => $waitlist->id]);

        $this->assertInstanceOf(Waitlist::class, $entry->waitlist);
        $this->assertEquals($waitlist->id, $entry->waitlist->id);
    }

    /** @test */
    public function it_belongs_to_appointment()
    {
        $appointment = Appointment::factory()->create();
        $entry = WaitlistEntry::factory()->create(['appointment_id' => $appointment->id]);

        $this->assertInstanceOf(Appointment::class, $entry->appointment);
        $this->assertEquals($appointment->id, $entry->appointment->id);
    }

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        $entry = new WaitlistEntry();

        $fillable = [
            'waitlist_id',
            'appointment_id',
            'slot_date',
            'slot_time',
            'offered_at',
            'response_deadline',
            'status',
        ];

        $this->assertEquals($fillable, $entry->getFillable());
    }

    /** @test */
    public function it_has_correct_casts()
    {
        $entry = new WaitlistEntry();

        $expectedCasts = [
            'id' => 'int',
            'slot_date' => 'date',
            'slot_time' => 'datetime',
            'offered_at' => 'datetime',
            'response_deadline' => 'datetime',
        ];

        $this->assertEquals($expectedCasts, $entry->getCasts());
    }

    /** @test */
    public function it_can_scope_pending_entries()
    {
        WaitlistEntry::factory()->count(3)->create(['status' => 'pending']);
        WaitlistEntry::factory()->count(2)->create(['status' => 'offered']);

        $pendingEntries = WaitlistEntry::pending()->get();

        $this->assertCount(3, $pendingEntries);
        $pendingEntries->each(function ($entry) {
            $this->assertEquals('pending', $entry->status);
        });
    }

    /** @test */
    public function it_can_scope_offered_entries()
    {
        WaitlistEntry::factory()->count(2)->create(['status' => 'offered']);
        WaitlistEntry::factory()->count(3)->create(['status' => 'pending']);

        $offeredEntries = WaitlistEntry::offered()->get();

        $this->assertCount(2, $offeredEntries);
        $offeredEntries->each(function ($entry) {
            $this->assertEquals('offered', $entry->status);
        });
    }

    /** @test */
    public function it_can_scope_accepted_entries()
    {
        WaitlistEntry::factory()->count(2)->create(['status' => 'accepted']);
        WaitlistEntry::factory()->count(3)->create(['status' => 'declined']);

        $acceptedEntries = WaitlistEntry::accepted()->get();

        $this->assertCount(2, $acceptedEntries);
        $acceptedEntries->each(function ($entry) {
            $this->assertEquals('accepted', $entry->status);
        });
    }

    /** @test */
    public function it_can_scope_declined_entries()
    {
        WaitlistEntry::factory()->count(2)->create(['status' => 'declined']);
        WaitlistEntry::factory()->count(3)->create(['status' => 'accepted']);

        $declinedEntries = WaitlistEntry::declined()->get();

        $this->assertCount(2, $declinedEntries);
        $declinedEntries->each(function ($entry) {
            $this->assertEquals('declined', $entry->status);
        });
    }

    /** @test */
    public function it_can_scope_expired_entries()
    {
        WaitlistEntry::factory()->count(2)->create(['status' => 'expired']);
        WaitlistEntry::factory()->count(3)->create(['status' => 'offered']);

        $expiredEntries = WaitlistEntry::expired()->get();

        $this->assertCount(2, $expiredEntries);
        $expiredEntries->each(function ($entry) {
            $this->assertEquals('expired', $entry->status);
        });
    }

    /** @test */
    public function it_can_scope_entries_with_expired_deadline()
    {
        $expiredEntry = WaitlistEntry::factory()->create([
            'status' => 'offered',
            'response_deadline' => now()->subHour(),
        ]);

        $validEntry = WaitlistEntry::factory()->create([
            'status' => 'offered',
            'response_deadline' => now()->addHour(),
        ]);

        $expiredDeadlineEntries = WaitlistEntry::expiredDeadline()->get();

        $this->assertCount(1, $expiredDeadlineEntries);
        $this->assertEquals($expiredEntry->id, $expiredDeadlineEntries->first()->id);
    }

    /** @test */
    public function it_can_check_if_pending()
    {
        $pendingEntry = WaitlistEntry::factory()->create(['status' => 'pending']);
        $offeredEntry = WaitlistEntry::factory()->create(['status' => 'offered']);

        $this->assertTrue($pendingEntry->isPending());
        $this->assertFalse($offeredEntry->isPending());
    }

    /** @test */
    public function it_can_check_if_offered()
    {
        $offeredEntry = WaitlistEntry::factory()->create(['status' => 'offered']);
        $pendingEntry = WaitlistEntry::factory()->create(['status' => 'pending']);

        $this->assertTrue($offeredEntry->isOffered());
        $this->assertFalse($pendingEntry->isOffered());
    }

    /** @test */
    public function it_can_check_if_accepted()
    {
        $acceptedEntry = WaitlistEntry::factory()->create(['status' => 'accepted']);
        $declinedEntry = WaitlistEntry::factory()->create(['status' => 'declined']);

        $this->assertTrue($acceptedEntry->isAccepted());
        $this->assertFalse($declinedEntry->isAccepted());
    }

    /** @test */
    public function it_can_check_if_declined()
    {
        $declinedEntry = WaitlistEntry::factory()->create(['status' => 'declined']);
        $acceptedEntry = WaitlistEntry::factory()->create(['status' => 'accepted']);

        $this->assertTrue($declinedEntry->isDeclined());
        $this->assertFalse($acceptedEntry->isDeclined());
    }

    /** @test */
    public function it_can_check_if_expired()
    {
        $expiredEntry = WaitlistEntry::factory()->create(['status' => 'expired']);
        $offeredEntry = WaitlistEntry::factory()->create(['status' => 'offered']);

        $this->assertTrue($expiredEntry->isExpired());
        $this->assertFalse($offeredEntry->isExpired());
    }

    /** @test */
    public function it_can_check_if_response_deadline_passed()
    {
        $expiredEntry = WaitlistEntry::factory()->create([
            'response_deadline' => now()->subHour(),
        ]);

        $validEntry = WaitlistEntry::factory()->create([
            'response_deadline' => now()->addHour(),
        ]);

        $entryWithoutDeadline = WaitlistEntry::factory()->create([
            'response_deadline' => null,
        ]);

        $this->assertTrue($expiredEntry->isResponseDeadlinePassed());
        $this->assertFalse($validEntry->isResponseDeadlinePassed());
        $this->assertFalse($entryWithoutDeadline->isResponseDeadlinePassed());
    }

    /** @test */
    public function it_can_mark_as_offered()
    {
        $entry = WaitlistEntry::factory()->create(['status' => 'pending']);

        $deadline = now()->addHours(24);
        $entry->markAsOffered($deadline);

        $this->assertEquals('offered', $entry->fresh()->status);
        $this->assertNotNull($entry->fresh()->offered_at);
        $this->assertEquals($deadline->toDateTimeString(), $entry->fresh()->response_deadline->toDateTimeString());
    }

    /** @test */
    public function it_can_mark_as_offered_with_default_deadline()
    {
        $entry = WaitlistEntry::factory()->create(['status' => 'pending']);

        $entry->markAsOffered();

        $this->assertEquals('offered', $entry->fresh()->status);
        $this->assertNotNull($entry->fresh()->offered_at);
        $this->assertNotNull($entry->fresh()->response_deadline);
        $this->assertEquals(
            now()->addHours(24)->format('Y-m-d H:i'),
            $entry->fresh()->response_deadline->format('Y-m-d H:i')
        );
    }

    /** @test */
    public function it_can_accept_entry()
    {
        $entry = WaitlistEntry::factory()->create(['status' => 'offered']);

        $entry->accept();

        $this->assertEquals('accepted', $entry->fresh()->status);
    }

    /** @test */
    public function it_can_decline_entry()
    {
        $entry = WaitlistEntry::factory()->create(['status' => 'offered']);

        $entry->decline();

        $this->assertEquals('declined', $entry->fresh()->status);
    }

    /** @test */
    public function it_can_expire_entry()
    {
        $entry = WaitlistEntry::factory()->create(['status' => 'offered']);

        $entry->expire();

        $this->assertEquals('expired', $entry->fresh()->status);
    }

    /** @test */
    public function it_can_get_formatted_slot_attribute()
    {
        $entry = WaitlistEntry::factory()->create([
            'slot_date' => '2025-11-18',
            'slot_time' => Carbon::parse('2025-11-18 09:00:00'),
        ]);

        $this->assertEquals('2025-11-18 09:00:00', $entry->formatted_slot);
    }

    /** @test */
    public function it_handles_factory_creation()
    {
        $entry = WaitlistEntry::factory()->create();

        $this->assertInstanceOf(WaitlistEntry::class, $entry);
        $this->assertNotNull($entry->waitlist_id);
        $this->assertNotNull($entry->slot_date);
        $this->assertNotNull($entry->slot_time);
        $this->assertNotNull($entry->status);
    }
}
