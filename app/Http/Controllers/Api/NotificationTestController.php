<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AppointmentBookedNotification;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class NotificationTestController extends Controller
{
    /**
     * Send a test notification to the authenticated user
     */
    public function sendTestNotification(Request $request)
    {
        try {
            $user = $request->user();

            Log::info('Sending test notification to user', ['user_id' => $user->id]);

            // Create a simple test notification
            $testNotification = new class($request->input('message', 'Test notification from backend diagnosis')) extends \Illuminate\Notifications\Notification implements \Illuminate\Contracts\Broadcasting\ShouldBroadcast {
                use \Illuminate\Bus\Queueable;

                protected $message;

                public function __construct($message) {
                    $this->message = $message;
                }

                public function via($notifiable) {
                    return ['database', 'broadcast'];
                }

                public function toArray($notifiable) {
                    return [
                        'type' => 'test_notification',
                        'title' => 'Test Notification',
                        'message' => $this->message,
                        'icon' => 'bell',
                        'data' => [
                            'test' => true,
                            'timestamp' => now()->toISOString()
                        ]
                    ];
                }

                public function toBroadcast($notifiable) {
                    return new \Illuminate\Notifications\Messages\BroadcastMessage([
                        'id' => $this->id,
                        'type' => 'test_notification',
                        'title' => 'Test Notification',
                        'message' => $this->message,
                        'body' => $this->message,
                        'icon' => 'bell',
                        'data' => [
                            'test' => true,
                            'timestamp' => now()->toISOString()
                        ],
                        'created_at' => now()->toISOString()
                    ]);
                }
            };

            // Send the notification
            $user->notify($testNotification);

            Log::info('Test notification sent successfully', ['user_id' => $user->id]);

            return response()->json([
                'success' => true,
                'message' => 'Test notification sent successfully',
                'user_id' => $user->id,
                'channel' => 'App.User.' . $user->id
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send test notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to send test notification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send a test appointment notification
     */
    public function sendTestAppointmentNotification(Request $request)
    {
        try {
            $user = $request->user();

            // Find or create a test appointment
            $appointment = Appointment::where('doctor_id', function($query) use ($user) {
                $query->select('id')
                      ->from('doctors')
                      ->where('user_id', $user->id)
                      ->limit(1);
            })->first();

            if (!$appointment) {
                // Create a dummy appointment for testing
                $appointment = new Appointment([
                    'id' => 999999,
                    'appointment_date' => now()->addDay(),
                    'appointment_type' => 'Test Consultation',
                    'status' => 'confirmed'
                ]);

                // Mock the doctor relationship
                $appointment->setRelation('doctor', (object)[
                    'user' => $user
                ]);
            }

            Log::info('Sending test appointment notification', [
                'user_id' => $user->id,
                'appointment_id' => $appointment->id
            ]);

            // Send the notification
            $user->notify(new AppointmentBookedNotification($appointment));

            Log::info('Test appointment notification sent successfully', ['user_id' => $user->id]);

            return response()->json([
                'success' => true,
                'message' => 'Test appointment notification sent successfully',
                'user_id' => $user->id,
                'appointment_id' => $appointment->id,
                'channel' => 'App.User.' . $user->id
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send test appointment notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to send test appointment notification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user notification preferences
     */
    public function getNotificationPreferences(Request $request)
    {
        try {
            $user = $request->user();
            $preferences = $user->getOrCreateNotificationPreferences();

            return response()->json([
                'success' => true,
                'preferences' => [
                    'appointment_booked' => $preferences->appointment_booked ?? true,
                    'email_notifications' => $preferences->email_notifications ?? true,
                    'sms_notifications' => $preferences->sms_notifications ?? false,
                    'push_notifications' => $preferences->push_notifications ?? true,
                ],
                'user_id' => $user->id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get preferences: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get queue status information
     */
    public function getQueueStatus(Request $request)
    {
        try {
            // Get queue stats
            $queueSize = Queue::size();

            // Check if any jobs failed recently
            $connection = config('queue.default');

            return response()->json([
                'success' => true,
                'queue_size' => $queueSize,
                'connection' => $connection,
                'realtime_queue_size' => Queue::size('realtime'),
                'default_queue_size' => Queue::size('default'),
                'message' => $queueSize > 0 ? 'Jobs pending in queue' : 'Queue is empty'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get queue status: ' . $e->getMessage(),
                'message' => 'Queue worker might not be running'
            ], 500);
        }
    }

    /**
     * Test Pusher configuration
     */
    public function testPusherConfig(Request $request)
    {
        try {
            $config = [
                'driver' => config('broadcasting.default'),
                'pusher' => [
                    'key' => config('broadcasting.connections.pusher.key'),
                    'cluster' => config('broadcasting.connections.pusher.options.cluster'),
                    'encrypted' => config('broadcasting.connections.pusher.options.encrypted'),
                    'host' => config('broadcasting.connections.pusher.options.host'),
                    'port' => config('broadcasting.connections.pusher.options.port'),
                ],
                'app_name' => config('app.name'),
                'app_env' => config('app.env')
            ];

            // Check if required settings are present
            $issues = [];
            if (!$config['pusher']['key']) {
                $issues[] = 'PUSHER_APP_KEY is not set';
            }
            if (!$config['pusher']['cluster']) {
                $issues[] = 'PUSHER_APP_CLUSTER is not set';
            }
            if ($config['driver'] !== 'pusher') {
                $issues[] = 'BROADCAST_DRIVER is not set to pusher';
            }

            return response()->json([
                'success' => count($issues) === 0,
                'config' => $config,
                'issues' => $issues,
                'message' => count($issues) === 0 ? 'Pusher configuration looks good' : 'Configuration issues found'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to check Pusher config: ' . $e->getMessage()
            ], 500);
        }
    }
}
