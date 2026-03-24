<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use BeyondCode\LaravelWebSockets\Facades\WebSocketsRouter;

class StartWebSocketServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websockets:serve 
                            {--host=0.0.0.0 : The host address to serve the WebSocket server on}
                            {--port=6001 : The port to serve the WebSocket server on}
                            {--debug : Enable debug mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the WebSocket server for ambient listening';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $host = $this->option('host');
        $port = $this->option('port');
        $debug = $this->option('debug');

        $this->info("Starting WebSocket server on {$host}:{$port}");
        
        if ($debug) {
            $this->info('Debug mode enabled');
        }

        // Check if Laravel WebSockets package is installed
        if (!class_exists('BeyondCode\LaravelWebSockets\WebSocketsServiceProvider')) {
            $this->error('Laravel WebSockets package is not installed.');
            $this->info('Install it with: composer require beyondcode/laravel-websockets');
            return 1;
        }

        // Load WebSocket routes
        if (file_exists(base_path('routes/websockets.php'))) {
            require base_path('routes/websockets.php');
            $this->info('WebSocket routes loaded');
        } else {
            $this->warn('WebSocket routes file not found at routes/websockets.php');
        }

        // Start the WebSocket server
        try {
            $this->call('websockets:serve', [
                '--host' => $host,
                '--port' => $port,
            ]);
        } catch (\Exception $e) {
            $this->error('Failed to start WebSocket server: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}