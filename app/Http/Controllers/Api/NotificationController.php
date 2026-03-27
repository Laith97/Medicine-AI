<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->data['title'] ?? 'Notification',
                    'message' => $notification->data['message'] ?? $notification->data['body'] ?? 'You have a new notification',
                    'data' => $notification->data,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                    'time_ago' => $notification->created_at->diffForHumans(),
                ];
            });

        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Get unread notifications only
     */
    public function unread(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $notifications = $user->unreadNotifications()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->data['title'] ?? 'Notification',
                    'message' => $notification->data['message'] ?? $notification->data['body'] ?? 'You have a new notification',
                    'data' => $notification->data,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                    'time_ago' => $notification->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'success' => true,
            'notifications' => $notifications,
            'count' => $notifications->count(),
        ]);
    }

    /**
     * Get unread count only
     */
    public function unreadCount(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'count' => 0,
                    'error' => 'Authentication required. Please log in again.',
                    'authenticated' => false
                ], 401);
            }

            $count = $user->unreadNotifications()->count();

            return response()->json([
                'success' => true,
                'count' => $count,
                'authenticated' => true
            ]);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Error in API unreadCount:' . $e->getMessage());

            // Always return valid JSON - don't expose internal error details
            return response()->json([
                'success' => false,
                'count' => 0,
                'error' => 'Failed to retrieve unread count',
                'authenticated' => false
            ]);
        }
    }

    /**
     * Mark a specific notification as read
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $notification = $user->notifications()->find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $user->unreadNotifications()->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read',
        ]);
    }

    /**
     * Delete a specific notification
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $notification = $user->notifications()->find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted',
        ]);
    }

    /**
     * Sync offline notifications when connection is restored
     */
    public function sync(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required',
                ], 401);
            }

            $notificationData = $request->all();

            // Create a new notification from the synced data
            $notification = $user->notifications()->create([
                'type' => $notificationData['type'] ?? 'App\\Notifications\\SystemAlertNotification',
                'data' => $notificationData['data'] ?? $notificationData,
                'read_at' => $notificationData['read_at'] ?? null,
            ]);

            // If the notification should be marked as read
            if (isset($notificationData['read_at']) && $notificationData['read_at']) {
                $notification->markAsRead();
            }

            Log::info('Offline notification synced', [
                'user_id' => $user->id,
                'notification_id' => $notification->id,
                'type' => $notification->type
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notification synced successfully',
                'notification_id' => $notification->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync offline notification: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to sync notification',
                'error' => 'Sync operation failed',
            ], 500);
        }
    }

    /**
     * Check for new notifications (used by service worker)
     */
    public function check(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required',
                ], 401);
            }

            $since = $request->get('since');
            $query = $user->notifications();

            if ($since) {
                $query->where('created_at', '>', $since);
            }

            $notifications = $query->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'type' => $notification->type,
                        'title' => $notification->data['title'] ?? 'Notification',
                        'message' => $notification->data['message'] ?? $notification->data['body'] ?? 'You have a new notification',
                        'data' => $notification->data,
                        'read_at' => $notification->read_at,
                        'created_at' => $notification->created_at,
                        'timestamp' => $notification->created_at->timestamp,
                    ];
                });

            return response()->json([
                'success' => true,
                'notifications' => $notifications,
                'count' => $notifications->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to check notifications: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to check notifications',
                'error' => 'Check operation failed',
            ], 500);
        }
    }

    /**
     * Get guest notifications (for non-authenticated access via token)
     *
     * Token format: guest_appt_{appointment_id}_{verification_token}
     */
    public function guestNotifications(Request $request, string $token): JsonResponse
    {
        try {
            // Rate limiting for public endpoint
            $rateLimitKey = 'guest_notifications:' . $request->ip();
            $cacheKey = 'rate_limit:' . $rateLimitKey;

            if (cache()->has($cacheKey)) {
                $attempts = cache()->get($cacheKey);
                if ($attempts >= 10) { // Max 10 requests per minute
                    return response()->json([
                        'success' => false,
                        'message' => 'Rate limit exceeded. Please try again later.',
                    ], 429);
                }
                cache()->put($cacheKey, $attempts + 1, 60);
            } else {
                cache()->put($cacheKey, 1, 60);
            }

            // Parse token format: guest_appt_{appointment_id}_{verification_token}
            $parts = explode('_', $token);

            // Validate token format has expected parts
            if (count($parts) < 4 || $parts[0] !== 'guest' || $parts[1] !== 'appt') {
                return response()->json([
                    'success' => false,
                    'notifications' => [],
                    'message' => 'Invalid token format',
                ], 401);
            }

            $appointmentId = intval($parts[2] ?? 0);
            $verificationToken = implode('_', array_slice($parts, 3));

            if ($appointmentId <= 0 || empty($verificationToken)) {
                return response()->json([
                    'success' => false,
                    'notifications' => [],
                    'message' => 'Invalid token',
                ], 401);
            }

            // Look up the guest appointment
            $appointment = Appointment::where('id', $appointmentId)
                ->where('verification_token', $verificationToken)
                ->whereNotNull('guest_email')
                ->where('token_expires_at', '>', now())
                ->first();

            if (!$appointment) {
                return response()->json([
                    'success' => false,
                    'notifications' => [],
                    'message' => 'Invalid or expired token',
                ], 401);
            }

            // Get notifications for this guest appointment
            // Notifications are stored with type and data containing appointment_id reference
            $notifications = Notification::where('type', 'like', '%Guest%')
                ->orWhere('data', 'like', '%"appointment_id":"' . $appointmentId . '"%')
                ->orWhere('data', 'like', '%"guest_email":"' . $appointment->guest_email . '"%')
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get()
                ->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'type' => $notification->type,
                        'title' => $notification->data['title'] ?? 'Notification',
                        'message' => $notification->data['message'] ?? $notification->data['body'] ?? 'You have a new notification',
                        'data' => $notification->data,
                        'read_at' => $notification->read_at,
                        'created_at' => $notification->created_at,
                        'time_ago' => $notification->created_at->diffForHumans(),
                    ];
                });

            return response()->json([
                'success' => true,
                'notifications' => $notifications,
                'count' => $notifications->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Guest notifications error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'notifications' => [],
                'message' => 'Failed to retrieve notifications',
            ], 500);
        }
    }
}
