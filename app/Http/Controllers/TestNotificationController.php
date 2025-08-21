<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\DatabaseNotification;
use App\Models\User;
use App\Notifications\TestNotification;
use App\Services\NotificationService;

class TestNotificationController extends Controller
{
    /**
     * Send a test notification to the current user
     */
    public function sendTestNotification(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $notificationData = [
            'title' => 'Real-time Test Notification',
            'message' => 'This is a test of the real-time notification system. If you see this toast, the system is working!',
            'type' => 'test_realtime',
            'icon' => 'bell',
            'link' => '/dashboard',
            'link_text' => 'View Dashboard'
        ];

        try {
            // Send notification using the notification service
            $notificationService = app(NotificationService::class);
            $notification = $notificationService->createNotification($user, $notificationData);

            // Also send via Laravel's notify method for testing
            $user->notify(new TestNotification($notificationData));

            return response()->json([
                'success' => true,
                'message' => 'Test notification sent successfully',
                'notification_id' => $notification->id,
                'user_id' => $user->id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send multiple test notifications
     */
    public function sendMultipleNotifications(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $notifications = [
            [
                'title' => 'First Test Notification',
                'message' => 'This is the first test notification',
                'type' => 'test_1',
                'icon' => 'bell',
                'link' => '/dashboard',
                'link_text' => 'View Dashboard'
            ],
            [
                'title' => 'Second Test Notification',
                'message' => 'This is the second test notification',
                'type' => 'test_2',
                'icon' => 'star',
                'link' => '/notifications',
                'link_text' => 'View All Notifications'
            ],
            [
                'title' => 'Third Test Notification',
                'message' => 'This is the third test notification',
                'type' => 'test_3',
                'icon' => 'info-circle',
                'link' => '/profile',
                'link_text' => 'View Profile'
            ]
        ];

        try {
            $notificationService = app(NotificationService::class);
            $sentNotifications = [];

            foreach ($notifications as $notificationData) {
                $notification = $notificationService->createNotification($user, $notificationData);
                /** @var \App\Models\User $user */
                $user->notify(new TestNotification($notificationData));
                $sentNotifications[] = $notification->id;
            }

            return response()->json([
                'success' => true,
                'message' => 'Multiple test notifications sent successfully',
                'notification_ids' => $sentNotifications,
                'user_id' => $user->id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test notification dropdown functionality
     */
    public function testDropdown(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $notificationData = [
            'title' => 'Dropdown Test Notification',
            'message' => 'This notification should appear in the dropdown and trigger a real-time update',
            'type' => 'dropdown_test',
            'icon' => 'envelope',
            'link' => '/notifications',
            'link_text' => 'View Notifications'
        ];

        try {
            $notificationService = app(NotificationService::class);
            $notification = $notificationService->createNotification($user, $notificationData);
            /** @var \App\Models\User $user */
            $user->notify(new TestNotification($notificationData));

            return response()->json([
                'success' => true,
                'message' => 'Dropdown test notification sent',
                'notification_id' => $notification->id,
                'user_id' => $user->id,
                'instructions' => 'Check your notification dropdown for real-time updates'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
