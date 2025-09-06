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
         * @param \Illuminate\Http\Request $request
         * @return \Illuminate\Http\Response|null
         */
        Broadcast::channel('App.User.{id}', function ($user, $id) {
            return (int) $user->id === (int) $id;
        });

        // Additional channel authorizations can be added here
        Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
            return (int) $user->id === (int) $id;
        });

        // Private user channel for notifications (matching the JavaScript subscription)
        Broadcast::channel('private-user.{id}', function ($user, $id) {
            return (int) $user->id === (int) $id;
        });

        // Debug channel for testing
        Broadcast::channel('debug.{id}', function ($user, $id) {
            return true; // Allow all connections for debugging
        });
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }
}
