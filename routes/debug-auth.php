<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/debug-broadcasting-auth', function (Request $request) {
        $user = Auth::user();

        return response()->json([
            'authenticated' => Auth::check(),
            'user_id' => $user ? $user->id : null,
            'user_name' => $user ? $user->name : null,
            'user_role' => $user ? $user->role : null,
            'session_id' => session()->getId(),
            'csrf_token' => csrf_token(),
            'request_headers' => $request->headers->all(),
            'pusher_auth_key' => env('VITE_PUSHER_APP_KEY'),
        ]);
    });

    Route::post('/debug-broadcasting-auth', function (Request $request) {
        $user = Auth::user();

        $channelName = $request->input('channel_name');
        $socketId = $request->input('socket_id');

        // Test channel authorization manually
        $authResult = null;
        if ($channelName === 'private-App.User.' . $user->id) {
            $authResult = 'Should be authorized';
        }

        return response()->json([
            'method' => 'POST',
            'authenticated' => Auth::check(),
            'user_id' => $user ? $user->id : null,
            'channel_name' => $channelName,
            'socket_id' => $socketId,
            'auth_result' => $authResult,
            'request_data' => $request->all(),
        ]);
    });
});
