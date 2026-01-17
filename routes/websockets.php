<?php

use App\WebSockets\MedicalAudioSocket;
use BeyondCode\LaravelWebSockets\Facades\WebSocketsRouter;

/*
|--------------------------------------------------------------------------
| WebSocket Routes
|--------------------------------------------------------------------------
|
| Here you can register WebSocket routes for your application. These
| routes are loaded by the WebSocketsServiceProvider within a group which
| contains the "websocket" middleware group.
|
*/

// Medical Audio WebSocket for ambient listening
WebSocketsRouter::webSocket('/ws/medical-audio', MedicalAudioSocket::class);

// Additional WebSocket routes can be added here
// WebSocketsRouter::webSocket('/ws/chat', ChatSocket::class);
// WebSocketsRouter::webSocket('/ws/notifications', NotificationSocket::class);