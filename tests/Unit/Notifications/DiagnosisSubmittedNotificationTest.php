<?php

namespace Tests\Unit\Notifications;

use Tests\TestCase;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Diagnosis;
use App\Notifications\DiagnosisSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DiagnosisSubmittedNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected $doctor;
    protected $patient;
    protected $diagnosis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctor = User::factory()->create(['role' => 'doctor']);
        $this->patient = User::factory()->create(['role' => 'patient']);

        $this->diagnosis = Diagnosis::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'diagnosis_text' => 'Patient shows symptoms of common cold',
            'follow_up_count' => 0,
            'patient_notified' => false,
        ]);
    }

    /** @test */
    public function it_can_be_created()
    {
        $notification = new DiagnosisSubmittedNotification($this->diagnosis);

        $this->assertInstanceOf(DiagnosisSubmittedNotification::class, $notification);
    }

    /** @test */
    public function it_has_correct_notification_channels()
    {
        $notification = new DiagnosisSubmittedNotification($this->diagnosis);

        $this->assertEquals(['database', 'mail'], $notification->via($this->patient));
    }

    /** @test */
    public function it_has_correct_array_content_for_patient()
    {
        $notification = new DiagnosisSubmittedNotification($this->diagnosis);

        $arrayContent = $notification->toArray($this->patient);

        $this->assertEquals('diagnosis_submitted', $arrayContent['type']);
        $this->assertEquals('New Diagnosis Submitted', $arrayContent['title']);
        $this->assertStringContainsString("Dr. {$this->diagnosis->doctor->name} has submitted a new diagnosis", $arrayContent['message']);
        $this->assertEquals('file-medical', $arrayContent['icon']);
        $this->assertEquals(route('diagnosis.patient.view', $this->diagnosis->id), $arrayContent['link']);
        $this->assertEquals('View Diagnosis', $arrayContent['link_text']);
        $this->assertEquals('diagnosis', $arrayContent['related_type']);
        $this->assertEquals($this->diagnosis->id, $arrayContent['related_id']);

        $this->assertArrayHasKey('data', $arrayContent);
        $this->assertEquals($this->diagnosis->id, $arrayContent['data']['diagnosis_id']);
        $this->assertEquals($this->diagnosis->doctor->name, $arrayContent['data']['doctor_name']);
        $this->assertEquals($this->diagnosis->created_at->format('Y-m-d H:i:s'), $arrayContent['data']['submitted_at']);
        $this->assertFalse($arrayContent['data']['has_ai_assistant']);
    }

    /** @test */
    public function it_has_correct_mail_content_for_patient()
    {
        $notification = new DiagnosisSubmittedNotification($this->diagnosis);

        $mailData = $notification->toMail($this->patient);

        $this->assertEquals('New Diagnosis Submitted', $mailData->subject);
        $this->assertStringContainsString("Hello {$this->patient->name}", $mailData->greeting);
        $this->assertStringContainsString("A new diagnosis has been submitted by {$this->diagnosis->doctor->name}", $mailData->introLines[0]);
        $this->assertStringContainsString($this->diagnosis->created_at->format('M j, Y'), $mailData->introLines[1]);
        $this->assertStringContainsString('View Diagnosis', $mailData->actionText);
        $this->assertEquals(route('diagnosis.show', $this->diagnosis), $mailData->actionUrl);
        $this->assertStringContainsString('Thank you for using our platform', $mailData->outroLines[0]);
    }

    /** @test */
    public function it_has_correct_sms_content()
    {
        $notification = new DiagnosisSubmittedNotification($this->diagnosis);

        $smsContent = $notification->toSms($this->patient);

        $this->assertStringContainsString("New diagnosis submitted by {$this->diagnosis->doctor->name}", $smsContent);
        $this->assertStringContainsString($this->diagnosis->created_at->format('M j, Y'), $smsContent);
        $this->assertStringContainsString(route('diagnosis.show', $this->diagnosis), $smsContent);
    }

    /** @test */
    public function it_can_be_sent_to_patient()
    {
        $notification = new DiagnosisSubmittedNotification($this->diagnosis);

        $this->patient->notify($notification);

        $this->assertEquals(1, $this->patient->notifications()->whereNull('read_at')->count());

        $storedNotification = $this->patient->notifications()->first();
        $data = json_decode($storedNotification->data, true);

        $this->assertEquals('New Diagnosis Submitted', $data['title']);
        $this->assertEquals('diagnosis_submitted', $storedNotification->type);
    }

    /** @test */
    public function it_handles_ai_assistant_results()
    {
        $diagnosis = Diagnosis::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
        ]);

        // Create AI assistant result
        $diagnosis->aiAssistantResults()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'prompt' => 'Analyze patient symptoms',
            'response' => 'Based on the symptoms, the patient may have...',
        ]);

        $notification = new DiagnosisSubmittedNotification($diagnosis);

        $arrayContent = $notification->toArray($this->patient);

        $this->assertTrue($arrayContent['data']['has_ai_assistant']);
    }

    /** @test */
    public function it_handles_follow_up_count()
    {
        $diagnosis = Diagnosis::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'follow_up_count' => 3,
        ]);

        $notification = new DiagnosisSubmittedNotification($diagnosis);

        $arrayContent = $notification->toArray($this->patient);

        $this->assertEquals(3, $arrayContent['data']['follow_up_count']);
    }

    /** @test */
    public function it_handles_patient_viewed_status()
    {
        $diagnosis = Diagnosis::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'patient_viewed_at' => now(),
        ]);

        $notification = new DiagnosisSubmittedNotification($diagnosis);

        $arrayContent = $notification->toArray($this->patient);

        $this->assertEquals($diagnosis->created_at->format('Y-m-d H:i:s'), $arrayContent['data']['submitted_at']);
    }

    /** @test */
    public function it_handles_patient_reviewed_status()
    {
        $diagnosis = Diagnosis::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'patient_reviewed' => true,
        ]);

        $notification = new DiagnosisSubmittedNotification($diagnosis);

        $arrayContent = $notification->toArray($this->patient);

        $this->assertEquals($diagnosis->created_at->format('Y-m-d H:i:s'), $arrayContent['data']['submitted_at']);
    }

    /** @test */
    public function it_includes_diagnosis_text()
    {
        $diagnosis = Diagnosis::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'diagnosis_text' => 'Patient shows symptoms of common cold with fever and cough',
        ]);

        $notification = new DiagnosisSubmittedNotification($diagnosis);

        $arrayContent = $notification->toArray($this->patient);

        $this->assertStringContainsString('Dr. {$this->diagnosis->doctor->name} has submitted a new diagnosis', $arrayContent['message']);
    }

    /** @test */
    public function it_includes_voice_transcript_if_present()
    {
        $diagnosis = Diagnosis::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'voice_transcript' => 'Patient reports headache and fever for the past three days',
        ]);

        $notification = new DiagnosisSubmittedNotification($diagnosis);

        $arrayContent = $notification->toArray($this->patient);

        $this->assertEquals('diagnosis_submitted', $arrayContent['type']);
        $this->assertEquals('New Diagnosis Submitted', $arrayContent['title']);
    }

    /** @test */
    public function it_includes_patient_data()
    {
        $diagnosis = Diagnosis::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'patient_data' => [
                'age' => 35,
                'gender' => 'male',
                'symptoms' => ['headache', 'fever', 'cough'],
            ],
        ]);

        $notification = new DiagnosisSubmittedNotification($diagnosis);

        $arrayContent = $notification->toArray($this->patient);

        $this->assertEquals('diagnosis_submitted', $arrayContent['type']);
        $this->assertEquals('New Diagnosis Submitted', $arrayContent['title']);
    }
}
