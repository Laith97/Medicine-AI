<?php

namespace App\Http\Controllers;

use App\Events\NewNotification;
use Illuminate\Http\Request;

class WebSocketController extends Controller
{
    public function sendNotification(Request $request)
    {
        $message = $request->input('message', 'Default notification message');
        
        // Broadcast the event
        event(new NewNotification($message));
        
        return response()->json(['status' => 'Notification sent']);
    }
    
    public function testPage()
    {
        return view('websocket-test');
    }
}