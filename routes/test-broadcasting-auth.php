<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;

// Test broadcasting auth endpoint
Route::post('/test-broadcasting-auth', function (\Illuminate\Http\Request $request) {
    \Illuminate\Support\Facades\Log::info('Broadcasting Auth Test', [
        'authenticated' => \Illuminate\Support\Facades\Auth::check(),
        'user_id' => \Illuminate\Support\Facades\Auth::id(),
        'channel_name' => $request->input('channel_name'),
        'socket_id' => $request->input('socket_id'),
        'headers' => $request->headers->all(),
        'request_data' => $request->all(),
        'session_id' => session()->getId(),
    ]);

    if (!\Illuminate\Support\Facades\Auth::check()) {
        return response()->json(['error' => 'Not authenticated'], 403);
    }

    $user = \Illuminate\Support\Facades\Auth::user();
    $channelName = $request->input('channel_name');
    $socketId = $request->input('socket_id');

    // Test channel authorization
    $authorized = false;
    $channelType = 'unknown';

    if (str_starts_with($channelName, 'private-App.User.')) {
        $channelType = 'App.User';
        $authorized = (int) $user->id === (int) substr($channelName, -strlen($user->id));
    } elseif (str_starts_with($channelName, 'private-user.')) {
        $channelType = 'user';
        $authorized = (int) $user->id === (int) substr($channelName, -strlen($user->id));
    } elseif (str_starts_with($channelName, 'private-App.Models.User.')) {
        $channelType = 'App.Models.User';
        $authorized = (int) $user->id === (int) substr($channelName, -strlen($user->id));
    }

    return response()->json([
        'success' => true,
        'authenticated' => \Illuminate\Support\Facades\Auth::check(),
        'user_id' => $user->id,
        'user_name' => $user->name,
        'user_role' => $user->role,
        'channel_name' => $channelName,
        'channel_type' => $channelType,
        'socket_id' => $socketId,
        'authorized' => $authorized,
        'session_id' => session()->getId(),
        'csrf_token' => csrf_token(),
    ]);
})->middleware(['web']);
