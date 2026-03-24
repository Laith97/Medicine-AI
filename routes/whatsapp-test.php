<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\User;
use App\Notifications\TestNotification;

// Test WhatsApp notification functionality
Route::get('/test-whatsapp-notification', function (Request $request) {
    // Find a test user (first user in the system)
    $user = User::first();
    
    if (!$user) {
        return response()->json(['error' => 'No users found in the system'], 404);
    }

    // Create a test notification
    $notification = new TestNotification([
        'title' => 'WhatsApp Test Notification',
        'message' => 'This is a test of the WhatsApp notification system. If you receive this, WhatsApp notifications are working correctly!',
        'type' => 'test',
        'icon' => 'whatsapp'
    ]);

    try {
        // Send the notification to the user
        $user->notify($notification);

        return response()->json([
            'success' => true,
            'message' => 'Test notification sent successfully',
            'user' => $user->name,
            'channels' => $notification->via($user)
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error sending notification: ' . $e->getMessage()
        ], 500);
    }
});

// Test WhatsApp notification with preferences
Route::get('/test-whatsapp-preferences', function (Request $request) {
    $user = User::first();
    
    if (!$user) {
        return response()->json(['error' => 'No users found in the system'], 404);
    }

    // Check if user has WhatsApp enabled
    $prefs = $user->getOrCreateNotificationPreferences();
    
    return response()->json([
        'user' => $user->name,
        'whatsapp_enabled' => $prefs->whatsapp_enabled,
        'whatsapp_appointment_reminders' => $prefs->whatsapp_appointment_reminders,
        'whatsapp_urgent_alerts' => $prefs->whatsapp_urgent_alerts,
        'whatsapp_diagnosis_updates' => $prefs->whatsapp_diagnosis_updates,
        'whatsapp_review_requests' => $prefs->whatsapp_review_requests,
        'wants_whatsapp_channel' => $user->wantsNotificationChannel('whatsapp'),
        'wants_notification_type' => $user->wantsNotification('appointment_booked')
    ]);
});