<?php

namespace Tests\Unit\Models;

use App\Models\DoctorNote;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Appointment;
use App\Models\Specialty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorNoteTest extends TestCase
{
    use RefreshDatabase;

    protected $doctorNote;
    protected $doctor;
    protected $doctorUser;
    protected $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctorUser = User::factory()->create(['role' => 'doctor']);
        $this->patient = User::factory()->create(['role' => 'patient']);

        $specialty = Specialty::factory()->create();
        $this->doctor = Doctor::factory()->create([
            'user_id' => $this->doctorUser->id,
            'specialty_id' => $specialty->id
        ]);

        $this->doctorNote = DoctorNote::factory()->create([
            'doctor_id' => $this->doctorUser->id,
            'patient_id' => $this->patient->id,
            'title' => 'Patient Consultation Notes',
            'note_text' => 'Patient presented with symptoms of...',
            'note_type' => 'text',
            'appointment_date' => now()->subDay(),
            'is_voice_note' => false,
            'audio_file_path' => null,
            'transcript' => null,
            'is_private' => false,
            'follow_up_required' => false,
            'shared_with_patient' => false
        ]);
    }

    public function test_doctor_note_can_be_created()
    {
        $this->assertInstanceOf(DoctorNote::class, $this->doctorNote);
        $this->assertEquals($this->doctorUser->id, $this->doctorNote->doctor_id);
        $this->assertEquals($this->patient->id, $this->doctorNote->patient_id);
        $this->assertEquals('Patient Consultation Notes', $this->doctorNote->title);
    }

    public function test_doctor_note_has_fillable_attributes()
    {
        $fillable = [
            'doctor_id', 'patient_id', 'appointment_id', 'title', 'note_text',
            'note_type', 'appointment_date', 'is_voice_note', 'audio_file_path',
            'transcript', 'audio_duration', 'is_private', 'tags', 'category',
            'follow_up_required', 'follow_up_date', 'shared_with_patient',
            'shared_at'
        ];

        $this->assertEquals($fillable, $this->doctorNote->getFillable());
    }

    public function test_doctor_note_casts_attributes_correctly()
    {
        $this->assertIsBool($this->doctorNote->is_voice_note);
        $this->assertIsBool($this->doctorNote->is_private);
        $this->assertIsBool($this->doctorNote->follow_up_required);
        $this->assertIsBool($this->doctorNote->shared_with_patient);

        if ($this->doctorNote->tags) {
            $this->assertIsArray($this->doctorNote->tags);
        }
    }

    public function test_doctor_note_doctor_relationship()
    {
        $this->assertInstanceOf(User::class, $this->doctorNote->doctor);
        $this->assertEquals($this->doctorUser->id, $this->doctorNote->doctor->id);
    }

    public function test_doctor_note_patient_relationship()
    {
        $this->assertInstanceOf(User::class, $this->doctorNote->patient);
        $this->assertEquals($this->patient->id, $this->doctorNote->patient->id);
    }

    public function test_doctor_note_appointment_relationship()
    {
        $appointment = Appointment::factory()->create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id
        ]);

        $noteWithAppointment = DoctorNote::factory()->create([
            'doctor_id' => $this->doctorUser->id,
            'appointment_id' => $appointment->id
        ]);

        $this->assertInstanceOf(Appointment::class, $noteWithAppointment->appointment);
        $this->assertEquals($appointment->id, $noteWithAppointment->appointment->id);
    }

    public function test_doctor_note_text_notes_scope()
    {
        $textNote = DoctorNote::factory()->create(['note_type' => 'text']);
        $voiceNote = DoctorNote::factory()->create(['note_type' => 'voice']);

        $textNotes = DoctorNote::textNotes()->get();

        $this->assertTrue($textNotes->contains($textNote));
        $this->assertFalse($textNotes->contains($voiceNote));
    }

    public function test_doctor_note_voice_notes_scope()
    {
        $textNote = DoctorNote::factory()->create(['note_type' => 'text']);
        $voiceNote = DoctorNote::factory()->create(['note_type' => 'voice']);

        $voiceNotes = DoctorNote::voiceNotes()->get();

        $this->assertTrue($voiceNotes->contains($voiceNote));
        $this->assertFalse($voiceNotes->contains($textNote));
    }

    public function test_doctor_note_private_scope()
    {
        $privateNote = DoctorNote::factory()->create(['is_private' => true]);
        $publicNote = DoctorNote::factory()->create(['is_private' => false]);

        $privateNotes = DoctorNote::private()->get();

        $this->assertTrue($privateNotes->contains($privateNote));
        $this->assertFalse($privateNotes->contains($publicNote));
    }

    public function test_doctor_note_shared_scope()
    {
        $sharedNote = DoctorNote::factory()->create(['shared_with_patient' => true]);
        $unsharedNote = DoctorNote::factory()->create(['shared_with_patient' => false]);

        $sharedNotes = DoctorNote::shared()->get();

        $this->assertTrue($sharedNotes->contains($sharedNote));
        $this->assertFalse($sharedNotes->contains($unsharedNote));
    }

    public function test_doctor_note_recent_scope()
    {
        $recentNote = DoctorNote::factory()->create(['created_at' => now()->subDays(5)]);
        $oldNote = DoctorNote::factory()->create(['created_at' => now()->subDays(35)]);

        $recentNotes = DoctorNote::recent(30)->get(); // Last 30 days

        $this->assertTrue($recentNotes->contains($recentNote));
        $this->assertFalse($recentNotes->contains($oldNote));
    }

    public function test_doctor_note_by_category_scope()
    {
        $consultationNote = DoctorNote::factory()->create(['category' => 'consultation']);
        $followUpNote = DoctorNote::factory()->create(['category' => 'follow_up']);

        $consultationNotes = DoctorNote::byCategory('consultation')->get();

        $this->assertTrue($consultationNotes->contains($consultationNote));
        $this->assertFalse($consultationNotes->contains($followUpNote));
    }

    public function test_doctor_note_requiring_follow_up_scope()
    {
        $followUpNote = DoctorNote::factory()->create(['follow_up_required' => true]);
        $regularNote = DoctorNote::factory()->create(['follow_up_required' => false]);

        $followUpNotes = DoctorNote::requiresFollowUp()->get();

        $this->assertTrue($followUpNotes->contains($followUpNote));
        $this->assertFalse($followUpNotes->contains($regularNote));
    }

    public function test_doctor_note_is_text_note_method()
    {
        $this->assertTrue($this->doctorNote->isTextNote());

        $this->doctorNote->note_type = 'voice';
        $this->assertFalse($this->doctorNote->isTextNote());
    }

    public function test_doctor_note_is_voice_note_method()
    {
        $this->assertFalse($this->doctorNote->isVoiceNote());

        $this->doctorNote->note_type = 'voice';
        $this->assertTrue($this->doctorNote->isVoiceNote());
    }

    public function test_doctor_note_is_private_method()
    {
        $this->assertFalse($this->doctorNote->isPrivate());

        $this->doctorNote->is_private = true;
        $this->assertTrue($this->doctorNote->isPrivate());
    }

    public function test_doctor_note_is_shared_with_patient_method()
    {
        $this->assertFalse($this->doctorNote->isSharedWithPatient());

        $this->doctorNote->shared_with_patient = true;
        $this->assertTrue($this->doctorNote->isSharedWithPatient());
    }

    public function test_doctor_note_requires_follow_up_method()
    {
        $this->assertFalse($this->doctorNote->requiresFollowUp());

        $this->doctorNote->follow_up_required = true;
        $this->assertTrue($this->doctorNote->requiresFollowUp());
    }

    public function test_doctor_note_get_truncated_content_attribute()
    {
        $longContent = str_repeat('This is a very long note content. ', 20);
        $this->doctorNote->note_text = $longContent;

        $truncated = $this->doctorNote->truncated_content;
        $this->assertTrue(strlen($truncated) <= 200);
        $this->assertStringEndsWith('...', $truncated);

        $shortContent = 'Short note';
        $this->doctorNote->note_text = $shortContent;
        $this->assertEquals($shortContent, $this->doctorNote->truncated_content);
    }

    public function test_doctor_note_get_formatted_date_attribute()
    {
        $date = now()->subDays(2);
        $this->doctorNote->created_at = $date;

        $this->assertEquals($date->format('M j, Y g:i A'), $this->doctorNote->formatted_date);
    }

    public function test_doctor_note_get_word_count_attribute()
    {
        $this->doctorNote->note_text = 'This is a test note with exactly ten words here.';
        $this->assertEquals(10, $this->doctorNote->word_count);

        $this->doctorNote->note_text = '';
        $this->assertEquals(0, $this->doctorNote->word_count);
    }

    public function test_doctor_note_get_reading_time_attribute()
    {
        // Assuming average reading speed of 200 words per minute
        $this->doctorNote->note_text = str_repeat('word ', 400); // 400 words
        $this->assertEquals(2, $this->doctorNote->reading_time); // 2 minutes

        $this->doctorNote->note_text = str_repeat('word ', 100); // 100 words
        $this->assertEquals(1, $this->doctorNote->reading_time); // 1 minute (minimum)
    }

    public function test_doctor_note_share_with_patient_method()
    {
        $this->doctorNote->shareWithPatient();

        $this->assertTrue($this->doctorNote->shared_with_patient);
        $this->assertNotNull($this->doctorNote->shared_at);
    }

    public function test_doctor_note_unshare_with_patient_method()
    {
        $this->doctorNote->shared_with_patient = true;
        $this->doctorNote->shared_at = now();
        $this->doctorNote->save();

        $this->doctorNote->unshareWithPatient();

        $this->assertFalse($this->doctorNote->shared_with_patient);
        $this->assertNull($this->doctorNote->shared_at);
    }

    public function test_doctor_note_mark_private_method()
    {
        $this->doctorNote->markPrivate();

        $this->assertTrue($this->doctorNote->is_private);
    }

    public function test_doctor_note_mark_public_method()
    {
        $this->doctorNote->is_private = true;
        $this->doctorNote->save();

        $this->doctorNote->markPublic();

        $this->assertFalse($this->doctorNote->is_private);
    }

    public function test_doctor_note_add_tag_method()
    {
        $this->doctorNote->addTag('urgent');
        $this->doctorNote->addTag('follow-up');

        $this->assertContains('urgent', $this->doctorNote->tags);
        $this->assertContains('follow-up', $this->doctorNote->tags);

        // Test that duplicate tags are not added
        $this->doctorNote->addTag('urgent');
        $this->assertEquals(2, count($this->doctorNote->tags));
    }

    public function test_doctor_note_remove_tag_method()
    {
        $this->doctorNote->tags = ['urgent', 'follow-up', 'consultation'];
        $this->doctorNote->save();

        $this->doctorNote->removeTag('urgent');

        $this->assertNotContains('urgent', $this->doctorNote->tags);
        $this->assertContains('follow-up', $this->doctorNote->tags);
        $this->assertContains('consultation', $this->doctorNote->tags);
    }

    public function test_doctor_note_has_tag_method()
    {
        $this->doctorNote->tags = ['urgent', 'follow-up'];
        $this->doctorNote->save();

        $this->assertTrue($this->doctorNote->hasTag('urgent'));
        $this->assertTrue($this->doctorNote->hasTag('follow-up'));
        $this->assertFalse($this->doctorNote->hasTag('consultation'));
    }

    public function test_doctor_note_set_follow_up_method()
    {
        $followUpDate = now()->addWeek();

        $this->doctorNote->setFollowUp($followUpDate);

        $this->assertTrue($this->doctorNote->follow_up_required);
        $this->assertEquals($followUpDate->toDateString(), $this->doctorNote->follow_up_date->toDateString());
    }

    public function test_doctor_note_clear_follow_up_method()
    {
        $this->doctorNote->follow_up_required = true;
        $this->doctorNote->follow_up_date = now()->addWeek();
        $this->doctorNote->save();

        $this->doctorNote->clearFollowUp();

        $this->assertFalse($this->doctorNote->follow_up_required);
        $this->assertNull($this->doctorNote->follow_up_date);
    }

    public function test_doctor_note_get_audio_duration_formatted_method()
    {
        $voiceNote = DoctorNote::factory()->create([
            'note_type' => 'voice',
            'audio_duration' => 125 // 2 minutes 5 seconds
        ]);

        $this->assertEquals('2:05', $voiceNote->getAudioDurationFormatted());

        $voiceNote->audio_duration = 65; // 1 minute 5 seconds
        $this->assertEquals('1:05', $voiceNote->getAudioDurationFormatted());

        $voiceNote->audio_duration = 30; // 30 seconds
        $this->assertEquals('0:30', $voiceNote->getAudioDurationFormatted());
    }

    public function test_doctor_note_search_scope()
    {
        $note1 = DoctorNote::factory()->create([
            'title' => 'Diabetes consultation',
            'note_text' => 'Patient has type 2 diabetes'
        ]);

        $note2 = DoctorNote::factory()->create([
            'title' => 'Hypertension follow-up',
            'note_text' => 'Blood pressure is well controlled'
        ]);

        $diabetesNotes = DoctorNote::search('diabetes')->get();
        $hypertensionNotes = DoctorNote::search('hypertension')->get();

        $this->assertTrue($diabetesNotes->contains($note1));
        $this->assertFalse($diabetesNotes->contains($note2));

        $this->assertTrue($hypertensionNotes->contains($note2));
        $this->assertFalse($hypertensionNotes->contains($note1));
    }
}
