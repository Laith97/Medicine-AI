<?php

namespace Tests\Unit\Models;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChatMessageTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $chatSession;
    protected $chatMessage;

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
            'title' => 'Medical Consultation'
        ]);

        $this->chatMessage = ChatMessage::factory()->create([
            'chat_session_id' => $this->chatSession->id,
            'message' => 'I have been experiencing headaches for the past week',
            'sender_type' => 'user',
            'sender_id' => $this->user->id
        ]);
    }

    public function test_chat_message_can_be_created()
    {
        $this->assertInstanceOf(ChatMessage::class, $this->chatMessage);
        $this->assertEquals('I have been experiencing headaches for the past week', $this->chatMessage->message);
        $this->assertEquals('user', $this->chatMessage->sender_type);
        $this->assertEquals($this->user->id, $this->chatMessage->sender_id);
    }

    public function test_chat_message_belongs_to_chat_session()
    {
        $this->assertInstanceOf(ChatSession::class, $this->chatMessage->chatSession);
        $this->assertEquals($this->chatSession->id, $this->chatMessage->chatSession->id);
    }

    public function test_chat_message_belongs_to_sender()
    {
        $this->assertInstanceOf(User::class, $this->chatMessage->sender);
        $this->assertEquals($this->user->id, $this->chatMessage->sender->id);
    }

    public function test_ai_message_creation()
    {
        $aiMessage = ChatMessage::factory()->create([
            'chat_session_id' => $this->chatSession->id,
            'message' => 'Based on your symptoms, I recommend seeing a doctor for proper evaluation.',
            'sender_type' => 'ai',
            'sender_id' => null
        ]);

        $this->assertEquals('ai', $aiMessage->sender_type);
        $this->assertNull($aiMessage->sender_id);
        $this->assertNull($aiMessage->sender);
    }

    public function test_chat_message_with_metadata()
    {
        $metadata = [
            'message_type' => 'symptom_description',
            'confidence_score' => 0.95,
            'entities_extracted' => ['headache', 'week', 'pain'],
            'sentiment' => 'concerned'
        ];

        $message = ChatMessage::factory()->create([
            'chat_session_id' => $this->chatSession->id,
            'message' => 'I have severe headaches',
            'sender_type' => 'user',
            'metadata' => json_encode($metadata)
        ]);

        $storedMetadata = json_decode($message->metadata, true);
        $this->assertEquals('symptom_description', $storedMetadata['message_type']);
        $this->assertEquals(0.95, $storedMetadata['confidence_score']);
        $this->assertContains('headache', $storedMetadata['entities_extracted']);
    }

    public function test_chat_message_can_be_flagged()
    {
        $this->assertFalse($this->chatMessage->is_flagged);

        $this->chatMessage->update(['is_flagged' => true]);
        $this->chatMessage->refresh();

        $this->assertTrue($this->chatMessage->is_flagged);
    }

    public function test_chat_message_with_attachments()
    {
        $attachments = [
            [
                'type' => 'image',
                'filename' => 'symptom_photo.jpg',
                'path' => '/uploads/images/symptom_photo.jpg',
                'size' => 1024000
            ],
            [
                'type' => 'document',
                'filename' => 'lab_results.pdf',
                'path' => '/uploads/documents/lab_results.pdf',
                'size' => 512000
            ]
        ];

        $message = ChatMessage::factory()->create([
            'chat_session_id' => $this->chatSession->id,
            'message' => 'Here are my test results',
            'sender_type' => 'user',
            'attachments' => json_encode($attachments)
        ]);

        $storedAttachments = json_decode($message->attachments, true);
        $this->assertCount(2, $storedAttachments);
        $this->assertEquals('image', $storedAttachments[0]['type']);
        $this->assertEquals('symptom_photo.jpg', $storedAttachments[0]['filename']);
    }

    public function test_chat_message_ai_processing_info()
    {
        $aiProcessing = [
            'model_used' => 'gpt-4',
            'tokens_used' => 150,
            'processing_time_ms' => 1200,
            'temperature' => 0.7,
            'max_tokens' => 500
        ];

        $aiMessage = ChatMessage::factory()->create([
            'chat_session_id' => $this->chatSession->id,
            'message' => 'Based on your symptoms, here is my analysis...',
            'sender_type' => 'ai',
            'ai_processing_info' => json_encode($aiProcessing)
        ]);

        $storedInfo = json_decode($aiMessage->ai_processing_info, true);
        $this->assertEquals('gpt-4', $storedInfo['model_used']);
        $this->assertEquals(150, $storedInfo['tokens_used']);
        $this->assertEquals(1200, $storedInfo['processing_time_ms']);
    }

    public function test_chat_message_can_be_edited()
    {
        $originalMessage = $this->chatMessage->message;
        $editedMessage = 'I have been experiencing severe headaches for the past week';

        $this->chatMessage->update([
            'message' => $editedMessage,
            'is_edited' => true,
            'edited_at' => now()
        ]);

        $this->assertEquals($editedMessage, $this->chatMessage->message);
        $this->assertTrue($this->chatMessage->is_edited);
        $this->assertNotNull($this->chatMessage->edited_at);
    }

    public function test_chat_message_response_time_tracking()
    {
        $userMessage = ChatMessage::factory()->create([
            'chat_session_id' => $this->chatSession->id,
            'message' => 'What should I do about my headache?',
            'sender_type' => 'user',
            'created_at' => now()
        ]);

        $aiResponse = ChatMessage::factory()->create([
            'chat_session_id' => $this->chatSession->id,
            'message' => 'I recommend taking over-the-counter pain medication...',
            'sender_type' => 'ai',
            'parent_message_id' => $userMessage->id,
            'created_at' => now()->addSeconds(3)
        ]);

        $responseTime = $aiResponse->created_at->diffInSeconds($userMessage->created_at);
        $this->assertEquals(3, $responseTime);
    }

    public function test_chat_message_can_have_parent_message()
    {
        $parentMessage = ChatMessage::factory()->create([
            'chat_session_id' => $this->chatSession->id,
            'message' => 'What are the symptoms of migraine?',
            'sender_type' => 'user'
        ]);

        $replyMessage = ChatMessage::factory()->create([
            'chat_session_id' => $this->chatSession->id,
            'message' => 'Migraine symptoms include severe headache, nausea...',
            'sender_type' => 'ai',
            'parent_message_id' => $parentMessage->id
        ]);

        $this->assertEquals($parentMessage->id, $replyMessage->parent_message_id);
    }

    public function test_chat_message_sentiment_analysis()
    {
        $sentimentData = [
            'sentiment' => 'negative',
            'confidence' => 0.85,
            'emotions' => ['anxiety', 'concern', 'pain'],
            'urgency_score' => 0.7
        ];

        $message = ChatMessage::factory()->create([
            'chat_session_id' => $this->chatSession->id,
            'message' => 'I am really worried about these severe headaches',
            'sender_type' => 'user',
            'sentiment_analysis' => json_encode($sentimentData)
        ]);

        $storedSentiment = json_decode($message->sentiment_analysis, true);
        $this->assertEquals('negative', $storedSentiment['sentiment']);
        $this->assertEquals(0.85, $storedSentiment['confidence']);
        $this->assertContains('anxiety', $storedSentiment['emotions']);
    }

    public function test_chat_message_can_be_marked_as_read()
    {
        $this->assertNull($this->chatMessage->read_at);

        $this->chatMessage->update(['read_at' => now()]);
        $this->chatMessage->refresh();

        $this->assertNotNull($this->chatMessage->read_at);
    }
}
