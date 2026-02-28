<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Bus\Queueable;

class DirectNotificationTestController extends Controller
{
    public function sendDirectTest(Request $request)
    {
        try {
            $user = $request->user();
            Log::info('Direct notification test started', ['user_id' => $user->id]);

            // Create a simple test notification that bypasses queue
            $testNotification = new class('Direct Test', 'This is a direct test notification') extends Notification implements ShouldBroadcast {
                use Queueable;

                protected $title;
                protected $message;

                public function __construct($title, $message) {
                    $this->title = $title;
                    $this->message = $message;
                    // Don't queue - send immediately
                    $this->connection = 'sync';
                }

                public function via($notifiable) {
                    return ['database', 'broadcast'];
                }

                public function toArray($notifiable) {
                    return [
                        'id' => $this->id ?? 'direct-' . time(),
                        'type' => 'direct_test',
                        'title' => $this->title,
                        'message' => $this->message,
                        'body' => $this->message,
                        'icon' => 'bell-alert',
                        'timestamp' => now()->toISOString(),
                        'test_mode' => true
                    ];
                }

                public function toBroadcast($notifiable) {
                    Log::info('Broadcasting notification', [
                        'channel' => 'App.User.' . $notifiable->id,
                        'title' => $this->title
                    ]);

                    return new BroadcastMessage([
                        'id' => $this->id ?? 'direct-' . time(),
                        'type' => 'direct_test',
                        'title' => $this->title,
                        'message' => $this->message,
                        'body' => $this->message,
                        'icon' => 'bell-alert',
                        'timestamp' => now()->toISOString(),
                        'test_mode' => true,
                        'created_at' => now()->toISOString()
                    ]);
                }

                public function broadcastOn() {
                    return ['App.User.' . $this->notifiable->id];
                }

                public function broadcastWith() {
                    return $this->toBroadcast($this->notifiable)->data;
                }
            };

            // Send notification synchronously
            $user->notify($testNotification);

            Log::info('Direct notification sent successfully');

            return response()->json([
                'success' => true,
                'message' => 'Direct notification sent successfully',
                'data' => [
                    'user_id' => $user->id,
                    'channel' => 'App.User.' . $user->id,
                    'title' => 'Direct Test',
                    'timestamp' => now()->toISOString(),
                    'broadcast_driver' => config('broadcasting.default'),
                    'queue_driver' => config('queue.default')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Direct notification test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Direct notification test failed: ' . $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    public function testPusherConnection(Request $request)
    {
        try {
            $user = $request->user();

            // Test Pusher connection directly
            $pusher = app('pusher');

            if (!$pusher) {
                return response()->json([
                    'success' => false,
                    'error' => 'Pusher not configured'
                ], 500);
            }

            $channel = 'App.User.' . $user->id;
            $event = 'test-connection';
            $data = [
                'title' => 'Pusher Connection Test',
                'message' => 'Testing direct Pusher connection at ' . now()->toISOString(),
                'timestamp' => now()->toISOString()
            ];

            Log::info('Testing direct Pusher connection', [
                'channel' => $channel,
                'event' => $event,
                'data' => $data
            ]);

            $result = $pusher->trigger($channel, $event, $data);

            return response()->json([
                'success' => true,
                'message' => 'Pusher connection test completed',
                'data' => [
                    'channel' => $channel,
                    'event' => $event,
                    'result' => $result,
                    'pusher_config' => [
                        'app_id' => config('broadcasting.connections.pusher.app_id'),
                        'cluster' => config('broadcasting.connections.pusher.options.cluster')
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Pusher connection test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Pusher connection test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getSystemStatus(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user_id' => $user->id,
            'channel' => 'App.User.' . $user->id,
            'broadcast_driver' => config('broadcasting.default'),
            'queue_driver' => config('queue.default'),
            'pusher_config' => [
                'app_id' => config('broadcasting.connections.pusher.app_id'),
                'app_key' => config('broadcasting.connections.pusher.key'),
                'cluster' => config('broadcasting.connections.pusher.options.cluster'),
                'host' => config('broadcasting.connections.pusher.options.host'),
                'scheme' => config('broadcasting.connections.pusher.options.scheme')
            ],
            'environment' => [
                'app_env' => app()->environment(),
                'app_debug' => config('app.debug'),
                'app_url' => config('app.url')
            ],
            'timestamp' => now()->toISOString()
        ]);
    }
}
