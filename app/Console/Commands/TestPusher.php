<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Pusher\Pusher;

class TestPusher extends Command
{
    protected $signature = 'test:pusher';
    protected $description = 'Test Pusher connection and credentials';

    public function handle()
    {
        $this->info('Testing Pusher connection...');

        try {
            $pusher = new Pusher(
                config('broadcasting.connections.pusher.key'),
                config('broadcasting.connections.pusher.secret'),
                config('broadcasting.connections.pusher.app_id'),
                [
                    'cluster' => config('broadcasting.connections.pusher.options.cluster'),
                    'useTLS' => true
                ]
            );

            $response = $pusher->get('/apps/' . config('broadcasting.connections.pusher.app_id'));
            $this->info('Pusher connection successful!');
            $this->info('Response: ' . json_encode($response));
        } catch (\Exception $e) {
            $this->error('Pusher connection failed: ' . $e->getMessage());
        }
    }
}
