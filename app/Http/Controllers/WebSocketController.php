<?php

namespace App\Http\Controllers;

use App\Events\NewNotification;
use Illuminate\Http\Request;

class WebSocketController extends Controller
{
    public function sendNotification(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:500',
        ]);

        // Broadcast only to the authenticated user - no cross-user targeting
        event(new NewNotification(['message' => $validated['message']], (int) $request->user()->id));

        return response()->json(['status' => 'Notification sent']);
    }
    
    public function testPage()
    {
        return view('websocket-test');
    }
}