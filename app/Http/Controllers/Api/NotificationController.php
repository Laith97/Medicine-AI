<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
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
            \Log::error('Error in API unreadCount:' . $e->getMessage());
            
            // Always return valid JSON
            return response()->json([
                'success' => false,
                'count' => 0,
                'error' => $e->getMessage(),
                'authenticated' => false
            ]);
        }
    }

    /**
     * Mark a specific notification as read
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
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
     * Get guest notifications (for non-authenticated access)
     */
    public function guestNotifications(Request $request, string $token): JsonResponse
    {
        // This could be used for guest appointment notifications
        // Implementation depends on your guest notification system

        return response()->json([
            'success' => true,
            'notifications' => [],
            'message' => 'Guest notifications feature not implemented yet',
        ]);
    }
}
