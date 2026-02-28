<?php

namespace Tests\Unit\Models;

use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChatSessionTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $chatSession;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'patient',
            'name' => 'Test Patient',
            'email' => 'patient@test.com'
        ]);

        $this->chatSession = ChatSession::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Medical Consultation',
            'status' => 'active'
        ]);
    }

    public function test_chat_session_can_be_created()
    {
        $this->assertInstanceOf(ChatSession::class, $this->chatSession);
        $this->assertEquals('Medical Consultation', $this->chatSession->title);
        $this->assertEquals('active', $this->chatSession->status);
        $this->assertEquals($this->user->id, $this->chatSession->user_id);
    }

    public function test_chat_session_belongs_to_user()
    {
        $this->assertInstanceOf(User::class, $this->chatSession->user);
        $this->assertEquals($this->user->id, $this->chatSession->user->id);
    }

    public function test_chat_session_has_many_messages()
    {
        $message1 = ChatMessage::factory()->create([
            'chat_session_id' => $this->chatSession->id,
            'message' => 'Hello, I need medical advice',
            'sender_type' => 'user'
        ]);

        $message2 = ChatMessage::factory()->create([
            'chat_session_id' => $this->chatSession->id,
            'message' => 'I can help you with that',
            'sender_type' => 'ai'
        ]);

        $this->assertCount(2, $this->chatSession->messages);
        $this->assertTrue($this->chatSession->messages->contains($message1));
        $this->assertTrue($this->chatSession->messages->contains($message2));
    }

    public function test_chat_session_can_be_closed()
    {
        $this->assertEquals('active', $this->chatSession->status);

        $this->chatSession->update(['status' => 'closed']);
        $this->chatSession->refresh();

        $this->assertEquals('closed', $this->chatSession->status);
    }

    public function test_chat_session_with_metadata()
    {
        $metadata = [
            'patient_age' => 35,
            'chief_complaint' => 'Headache',
            'session_type' => 'consultation',
            'priority' => 'normal'
        ];

        $session = ChatSession::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Headache Consultation',
            'metadata' => json_encode($metadata)
        ]);

        $storedMetadata = json_decode($session->metadata, true);
        $this->assertEquals(35, $storedMetadata['patient_age']);
        $this->assertEquals('Headache', $storedMetadata['chief_complaint']);
        $this->assertEquals('consultation', $storedMetadata['session_type']);
    }

    public function test_chat_session_can_have_openai_thread_id()
    {
        $threadId = 'thread_abc123xyz';

        $this->chatSession->update(['openai_thread_id' => $threadId]);
        $this->chatSession->refresh();

        $this->assertEquals($threadId, $this->chatSession->openai_thread_id);
    }

    public function test_chat_session_tracks_message_count()
    {
        // Create multiple messages
        ChatMessage::factory()->count(5)->create([
            'chat_session_id' => $this->chatSession->id
        ]);

        $this->assertEquals(5, $this->chatSession->messages()->count());
    }

    public function test_chat_session_can_be_archived()
    {
        $this->chatSession->update([
            'status' => 'archived',
            'archived_at' => now()
        ]);

        $this->assertEquals('archived', $this->chatSession->status);
        $this->assertNotNull($this->chatSession->archived_at);
    }

    public function test_chat_session_duration_calculation()
    {
        $startTime = now()->subHour();
        $endTime = now();

        $session = ChatSession::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => $startTime,
            'updated_at' => $endTime
        ]);

        $duration = $session->updated_at->diffInMinutes($session->created_at);
        $this->assertEquals(60, $duration);
    }

    public function test_chat_session_with_summary()
    {
        $summary = [
            'total_messages' => 10,
            'user_messages' => 5,
            'ai_messages' => 5,
            'main_topics' => ['headache', 'medication', 'follow-up'],
            'resolution_status' => 'resolved'
        ];

        $this->chatSession->update([
            'summary' => json_encode($summary),
            'status' => 'completed'
        ]);

        $storedSummary = json_decode($this->chatSession->summary, true);
        $this->assertEquals(10, $storedSummary['total_messages']);
        $this->assertContains('headache', $storedSummary['main_topics']);
        $this->assertEquals('resolved', $storedSummary['resolution_status']);
    }

    public function test_chat_session_can_have_doctor_assignment()
    {
        $doctor = User::factory()->create(['role' => 'doctor']);

        $this->chatSession->update(['assigned_doctor_id' => $doctor->id]);
        $this->chatSession->refresh();

        $this->assertEquals($doctor->id, $this->chatSession->assigned_doctor_id);
    }

    public function test_chat_session_priority_levels()
    {
        $priorities = ['low', 'normal', 'high', 'urgent'];

        foreach ($priorities as $priority) {
            $session = ChatSession::factory()->create([
                'user_id' => $this->user->id,
                'priority' => $priority
            ]);

            $this->assertEquals($priority, $session->priority);
        }
    }

    public function test_chat_session_can_track_ai_usage()
    {
        $aiUsage = [
            'total_tokens' => 1500,
            'prompt_tokens' => 800,
            'completion_tokens' => 700,
            'model_used' => 'gpt-4',
            'cost_estimate' => 0.045
        ];

        $this->chatSession->update(['ai_usage' => json_encode($aiUsage)]);

        $storedUsage = json_decode($this->chatSession->ai_usage, true);
        $this->assertEquals(1500, $storedUsage['total_tokens']);
        $this->assertEquals('gpt-4', $storedUsage['model_used']);
        $this->assertEquals(0.045, $storedUsage['cost_estimate']);
    }
}
