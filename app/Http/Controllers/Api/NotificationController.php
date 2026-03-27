<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
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
     * TODO: Implement guest notification lookup using token
     *       - Token should reference a guest_appointment or similar record
     *       - Return notifications associated with that guest appointment
     *       - Consider rate limiting since this is public-facing
     */
    public function guestNotifications(Request $request, string $token): JsonResponse
    {
        // TODO: Implement guest notification lookup
        // 1. Validate token format/prefix (e.g., guest_appt_xxx)
        // 2. Look up associated appointment/notification records
        // 3. Return matching notifications

        return response()->json([
            'success' => false,
            'notifications' => [],
            'message' => 'Guest notifications feature not implemented yet',
        ], 501);
    }
}
