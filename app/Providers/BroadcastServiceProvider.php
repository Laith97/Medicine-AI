<?php

namespace App\Providers;

use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Broadcast;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Broadcast::routes(['middleware' => ['web']]);

        /*
         * Authenticate the user's channel socket connection.
         *
         * Additional channel authorizations are defined in routes/channels.php
         */
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }
}
