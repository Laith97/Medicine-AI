<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\WebSockets\MedicalAudioSocket;

class WebSocketServer extends Command
{
    protected $signature = 'websocket:serve {--port=6001}';
    protected $description = 'Start the WebSocket server for medical audio streaming';

    public function handle()
    {
        $port = $this->option('port');
        
        $this->info("Starting Medical Audio WebSocket server on port {$port}...");
        
        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new MedicalAudioSocket()
                )
            ),
            $port
        );

        $this->info("Medical Audio WebSocket server started successfully!");
        $this->info("Listening on ws://localhost:{$port}");
        
        $server->run();
    }
}