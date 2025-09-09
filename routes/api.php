<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\NotificationTestController;
use App\Http\Controllers\UserSettingsController;
use App\Http\Controllers\Api\TelehealthAIController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| Notification API Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:web'])->group(function () {
    // User settings
    Route::get('/user/settings', [UserSettingsController::class, 'getSettings']);

    // Get notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread', [NotificationController::class, 'unread']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);

    // Mark as read
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);

    // Delete notifications
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    // Notification testing and diagnosis routes
    Route::post('/test-notification', [NotificationTestController::class, 'sendTestNotification']);
    Route::post('/test-appointment-notification', [NotificationTestController::class, 'sendTestAppointmentNotification']);
    Route::post('/test/notification', [NotificationTestController::class, 'sendEnhancedTestNotification']);
    Route::get('/notification-preferences', [NotificationTestController::class, 'getNotificationPreferences']);
    Route::get('/queue-status', [NotificationTestController::class, 'getQueueStatus']);
    Route::get('/pusher-config', [NotificationTestController::class, 'testPusherConfig']);

    // Direct notification testing (bypasses queue) - import controller
    Route::post('/test/direct-notification', [\App\Http\Controllers\Api\DirectNotificationTestController::class, 'sendDirectTest']);
    Route::post('/test/pusher-connection', [\App\Http\Controllers\Api\DirectNotificationTestController::class, 'testPusherConnection']);
    Route::get('/test/system-status', [\App\Http\Controllers\Api\DirectNotificationTestController::class, 'getSystemStatus']);

    // Telehealth AI routes
    Route::post('/telehealth/emotion', [TelehealthAIController::class, 'detectEmotion']);
    Route::post('/telehealth/engagement', [TelehealthAIController::class, 'trackEngagement']);
    Route::get('/telehealth/emotion-summary/{appointment_id}', [TelehealthAIController::class, 'getEmotionSummary']);
    Route::get('/telehealth/engagement-summary/{appointment_id}', [TelehealthAIController::class, 'getEngagementSummary']);
});

// Public routes (for guest access with token verification)
Route::get('/notifications/guest/{token}', [NotificationController::class, 'guestNotifications']);
