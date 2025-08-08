<?php

namespace Tests\Unit\Services;

use App\Services\ChatService;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\OpenAIClient;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class ChatServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $chatService;
    protected $openAIClientMock;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->openAIClientMock = Mockery::mock(OpenAIClient::class);
        $this->app->instance(OpenAIClient::class, $this->openAIClientMock);

        $this->chatService = new ChatService();

        $this->user = User::factory()->create([
            'role' => 'patient',
            'name' => 'Test Patient',
            'email' => 'patient@test.com'
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_create_chat_session()
    {
        $sessionData = [
            'title' => 'Medical Consultation',
            'metadata' => ['chief_complaint' => 'headache']
        ];

        $session = $this->chatService->createChatSession($this->user, $sessionData);

        $this->assertInstanceOf(ChatSession::class, $session);
        $this->assertEquals('Medical Consultation', $session->title);
        $this->assertEquals($this->user->id, $session->user_id);
        $this->assertEquals('active', $session->status);
    }

    public function test_add_user_message()
    {
        $session = ChatSession::factory()->create(['user_id' => $this->user->id]);
        $messageText = 'I have been experiencing headaches';

        $message = $this->chatService->addUserMessage($session, $this->user, $messageText);

        $this->assertInstanceOf(ChatMessage::class, $message);
        $this->assertEquals($messageText, $message->message);
        $this->assertEquals('user', $message->sender_type);
        $this->assertEquals($this->user->id, $message->sender_id);
        $this->assertEquals($session->id, $message->chat_session_id);
    }

    public function test_add_ai_message()
    {
        $session = ChatSession::factory()->create(['user_id' => $this->user->id]);
        $aiResponse = 'Based on your symptoms, I recommend...';
        $metadata = ['model_used' => 'gpt-4', 'tokens_used' => 150];

        $message = $this->chatService->addAIMessage($session, $aiResponse, $metadata);

        $this->assertInstanceOf(ChatMessage::class, $message);
        $this->assertEquals($aiResponse, $message->message);
        $this->assertEquals('ai', $message->sender_type);
        $this->assertNull($message->sender_id);
        $this->assertEquals($session->id, $message->chat_session_id);
        $this->assertEquals(json_encode($metadata), $message->ai_processing_info);
    }

    public function test_process_user_message_with_ai_response()
    {
        $session = ChatSession::factory()->create(['user_id' => $this->user->id]);
        $userMessage = 'What should I do about my headache?';
        $aiResponse = 'For headaches, you can try over-the-counter pain relievers...';

        $this->openAIClientMock
            ->shouldReceive('ask')
            ->once()
            ->with(Mockery::type('string'))
            ->andReturn($aiResponse);

        $result = $this->chatService->processUserMessage($session, $this->user, $userMessage);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('user_message', $result);
        $this->assertArrayHasKey('ai_message', $result);
        $this->assertInstanceOf(ChatMessage::class, $result['user_message']);
        $this->assertInstanceOf(ChatMessage::class, $result['ai_message']);
        $this->assertEquals($userMessage, $result['user_message']->message);
        $this->assertEquals($aiResponse, $result['ai_message']->message);
    }

    public function test_get_chat_history()
    {
        $session = ChatSession::factory()->create(['user_id' => $this->user->id]);

        ChatMessage::factory()->count(5)->create([
            'chat_session_id' => $session->id,
            'sender_type' => 'user',
            'sender_id' => $this->user->id
        ]);

        ChatMessage::factory()->count(5)->create([
            'chat_session_id' => $session->id,
            'sender_type' => 'ai'
        ]);

        $history = $this->chatService->getChatHistory($session);

        $this->assertCount(10, $history);
        $this->assertEquals($session->id, $history->first()->chat_session_id);
    }

    public function test_close_chat_session()
    {
        $session = ChatSession::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active'
        ]);

        $summary = [
            'total_messages' => 10,
            'resolution_status' => 'resolved'
        ];

        $closedSession = $this->chatService->closeChatSession($session, $summary);

        $this->assertEquals('closed', $closedSession->status);
        $this->assertEquals(json_encode($summary), $closedSession->summary);
        $this->assertNotNull($closedSession->closed_at);
    }

    public function test_get_user_chat_sessions()
    {
        ChatSession::factory()->count(3)->create(['user_id' => $this->user->id]);
        ChatSession::factory()->count(2)->create(); // Other user's sessions

        $userSessions = $this->chatService->getUserChatSessions($this->user);

        $this->assertCount(3, $userSessions);
        foreach ($userSessions as $session) {
            $this->assertEquals($this->user->id, $session->user_id);
        }
    }

    public function test_search_messages_in_session()
    {
        $session = ChatSession::factory()->create(['user_id' => $this->user->id]);

        ChatMessage::factory()->create([
            'chat_session_id' => $session->id,
            'message' => 'I have a headache',
            'sender_type' => 'user'
        ]);

        ChatMessage::factory()->create([
            'chat_session_id' => $session->id,
            'message' => 'Try taking ibuprofen for the headache',
            'sender_type' => 'ai'
        ]);

        ChatMessage::factory()->create([
            'chat_session_id' => $session->id,
            'message' => 'My stomach hurts',
            'sender_type' => 'user'
        ]);

        $results = $this->chatService->searchMessagesInSession($session, 'headache');

        $this->assertCount(2, $results);
        foreach ($results as $message) {
            $this->assertStringContainsString('headache', strtolower($message->message));
        }
    }

    public function test_generate_session_summary()
    {
        $session = ChatSession::factory()->create(['user_id' => $this->user->id]);

        ChatMessage::factory()->count(5)->create([
            'chat_session_id' => $session->id,
            'sender_type' => 'user'
        ]);

        ChatMessage::factory()->count(5)->create([
            'chat_session_id' => $session->id,
            'sender_type' => 'ai'
        ]);

        $summary = $this->chatService->generateSessionSummary($session);

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('total_messages', $summary);
        $this->assertArrayHasKey('user_messages', $summary);
        $this->assertArrayHasKey('ai_messages', $summary);
        $this->assertEquals(10, $summary['total_messages']);
        $this->assertEquals(5, $summary['user_messages']);
        $this->assertEquals(5, $summary['ai_messages']);
    }

    public function test_flag_message()
    {
        $session = ChatSession::factory()->create(['user_id' => $this->user->id]);
        $message = ChatMessage::factory()->create([
            'chat_session_id' => $session->id,
            'is_flagged' => false
        ]);

        $flaggedMessage = $this->chatService->flagMessage($message, 'Inappropriate content');

        $this->assertTrue($flaggedMessage->is_flagged);
        $this->assertEquals('Inappropriate content', $flaggedMessage->flag_reason);
        $this->assertNotNull($flaggedMessage->flagged_at);
    }

    public function test_get_active_sessions_count()
    {
        ChatSession::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'status' => 'active'
        ]);

        ChatSession::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'status' => 'closed'
        ]);

        $activeCount = $this->chatService->getActiveSessionsCount($this->user);

        $this->assertEquals(3, $activeCount);
    }

    public function test_archive_old_sessions()
    {
        $oldSession = ChatSession::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'closed',
            'updated_at' => now()->subDays(31)
        ]);

        $recentSession = ChatSession::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'closed',
            'updated_at' => now()->subDays(15)
        ]);

        $archivedCount = $this->chatService->archiveOldSessions(30);

        $oldSession->refresh();
        $recentSession->refresh();

        $this->assertEquals(1, $archivedCount);
        $this->assertEquals('archived', $oldSession->status);
        $this->assertEquals('closed', $recentSession->status);
    }

    public function test_get_session_statistics()
    {
        $session = ChatSession::factory()->create(['user_id' => $this->user->id]);

        ChatMessage::factory()->count(3)->create([
            'chat_session_id' => $session->id,
            'sender_type' => 'user',
            'created_at' => now()->subMinutes(30)
        ]);

        ChatMessage::factory()->count(3)->create([
            'chat_session_id' => $session->id,
            'sender_type' => 'ai',
            'created_at' => now()->subMinutes(25)
        ]);

        $stats = $this->chatService->getSessionStatistics($session);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_messages', $stats);
        $this->assertArrayHasKey('user_messages', $stats);
        $this->assertArrayHasKey('ai_messages', $stats);
        $this->assertArrayHasKey('duration_minutes', $stats);
        $this->assertEquals(6, $stats['total_messages']);
        $this->assertEquals(3, $stats['user_messages']);
        $this->assertEquals(3, $stats['ai_messages']);
    }
}
