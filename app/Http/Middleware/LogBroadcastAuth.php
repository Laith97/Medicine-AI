<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogBroadcastAuth
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($response->status() !== 200) {
            Log::warning('Broadcast auth DENIED', [
                'status' => $response->status(),
                'channel_name' => $request->input('channel_name'),
                'channels' => $request->input('channels'),
                'user_id' => $request->user()?->id,
                'role' => $request->user()?->role,
            ]);
        }

        return $response;
    }
}
