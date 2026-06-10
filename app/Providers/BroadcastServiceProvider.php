<?php

namespace App\Providers;

use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Exclude broadcasting auth from CSRF verification
        VerifyCsrfToken::except(['broadcasting/auth', 'broadcasting/auth/*', 'pusher/auth']);

        // Register channel authorization callbacks FIRST
        require base_path('routes/channels.php');

        // THEN register broadcasting routes
        // Uses web and auth middleware for session and authentication
        Broadcast::routes(['middleware' => ['web', 'auth']]);

        // Log for debugging
        if (config('app.debug')) {
            Log::info('BroadcastServiceProvider booted - broadcasting routes registered');
        }
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }
}
