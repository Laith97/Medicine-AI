<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\DatabaseNotification;
use App\Models\User;
use App\Models\NotificationType;
use App\Models\NotificationPreference;

class NotificationController extends Controller
{
    /**
     * Display a listing of the user's notifications.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get all notifications
        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Get unread notifications
        $unreadNotifications = $user->notifications()
            ->whereNull('read_at')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Get appointment notifications
        $appointmentNotifications = $user->notifications()
            ->whereJsonContains('data->type', 'appointment_booked')
            ->orWhere('type', 'LIKE', '%Appointment%')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Get diagnosis notifications
        $diagnosisNotifications = $user->notifications()
            ->whereJsonContains('data->type', 'diagnosis')
            ->orWhere('type', 'LIKE', '%Diagnosis%')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Get system notifications
        $systemNotifications = $user->notifications()
            ->whereJsonContains('data->type', 'system')
            ->orWhere('type', 'LIKE', '%System%')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Get unread count
        $unreadCount = $user->unreadNotifications()->count();

        return view('notifications.index', compact(
            'notifications',
            'unreadNotifications',
            'appointmentNotifications',
            'diagnosisNotifications',
            'systemNotifications',
            'unreadCount'
        ));
    }

    /**
     * Get notifications for API (used by notification dropdown).
     */
    public function apiIndex()
    {
        $user = Auth::user();

        // Get recent notifications (both read and unread) for dropdown
        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->take(15)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'data' => $notification->data,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                    'title' => $notification->data['title'] ?? 'Notification',
                    'message' => $notification->data['message'] ?? 'You have a new notification',
                ];
            });

        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }

    /**
     * Get notifications for the dropdown (AJAX).
     */
    public function dropdown()
    {
        $user = Auth::user();

        // Get recent notifications (both read and unread) for dropdown
        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'data' => $notification->data,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                    'title' => $notification->data['title'] ?? 'Notification',
                    'message' => $notification->data['message'] ?? 'You have a new notification',
                ];
            });

        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead($id)
    {
        $userId = Auth::id();
        $notification = DatabaseNotification::where('id', $id)
            ->where('notifiable_id', $userId)
            ->where('notifiable_type', get_class(Auth::user()))
            ->first();

        if ($notification) {
            $notification->markAsRead();
            event(new \App\Events\NotificationRead($userId, $notification->id));
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Notification not found'], 404);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        $user->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * Delete a notification.
     */
    public function destroy($id)
    {
        $notification = \Illuminate\Notifications\DatabaseNotification::where('id', $id)
            ->where('notifiable_id', Auth::id())
            ->where('notifiable_type', get_class(Auth::user()))
            ->first();

        if ($notification) {
            $notification->delete();
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Notification not found'], 404);
    }

    /**
     * Get unread notification count.
     */
    public function unreadCount()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                // Return a JSON response with a 401 status code
                return response()->json([
                    'count' => 0,
                    'error' => 'Authentication required. Please log in again.',
                    'authenticated' => false
                ], 401);
            }

            $count = $user->unreadNotifications()->count();
            return response()->json([
                'count' => $count,
                'authenticated' => true
            ]);
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Error in unreadCount:' . $e->getMessage());
            
            // Always return valid JSON
            return response()->json([
                'count' => 0,
                'error' => $e->getMessage(),
                'authenticated' => false
            ]);
        }
    }

    /**
     * Show notification settings page.
     */
    public function settings()
    {
        $user = Auth::user();
        $preferences = $user->notificationPreferences ?? new NotificationPreference();
        $preferences->user_id = $user->id;

        // For now, we'll use a simple array of notification types
        $notificationTypes = [
            ['id' => 'appointment', 'name' => 'Appointment Notifications'],
            ['id' => 'diagnosis', 'name' => 'Diagnosis Notifications'],
            ['id' => 'review', 'name' => 'Review Notifications'],
            ['id' => 'system', 'name' => 'System Notifications'],
        ];

        return view('notifications.settings', compact('user', 'preferences', 'notificationTypes'));
    }

    /**
     * Update notification settings.
     */
    public function updateSettings(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'email_notifications' => 'sometimes|boolean',
            'push_notifications' => 'sometimes|boolean',
            'sms_notifications' => 'sometimes|boolean',
            'frequency' => 'sometimes|string|in:immediate,daily,weekly',
        ]);

        // Get or create notification preferences
        $preferences = $user->notificationPreferences ?? new NotificationPreference();
        $preferences->fill($validated);
        $preferences->user_id = $user->id;
        $preferences->save();

        return response()->json(['success' => true, 'message' => 'Settings updated successfully']);
    }

    /**
     * Show notification preferences page.
     */
    public function preferences()
    {
        $user = Auth::user();
        // For now, we'll use a simple array of notification types
        $notificationTypes = [
            ['id' => 'appointment', 'name' => 'Appointment Notifications'],
            ['id' => 'diagnosis', 'name' => 'Diagnosis Notifications'],
            ['id' => 'review', 'name' => 'Review Notifications'],
            ['id' => 'system', 'name' => 'System Notifications'],
        ];
        $userPreferences = NotificationPreference::where('user_id', $user->id)->get();

        return view('notifications.preferences', compact('notificationTypes', 'userPreferences'));
    }

    /**
     * Update notification preferences.
     */
    public function updatePreferences(Request $request)
    {
        $user = Auth::user();
        $preferences = $request->input('preferences', []);

        foreach ($preferences as $typeId => $prefData) {
            NotificationPreference::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'notification_type_id' => $typeId,
                ],
                [
                    'enabled' => isset($prefData['enabled']),
                    'channels' => json_encode($prefData['channels'] ?? ['database']),
                ]
            );
        }

        return redirect()->route('notification.preferences')
            ->with('success', 'Notification preferences updated successfully!');
    }
}
