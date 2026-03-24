<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\WhatsAppNotificationService;
use App\Notifications\Channels\WhatsAppChannel;

class WhatsAppServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(WhatsAppNotificationService::class, function ($app) {
            return new WhatsAppNotificationService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register the WhatsApp notification channel
        \Illuminate\Support\Facades\Notification::extend('whatsapp', function ($app) {
            return new WhatsAppChannel(
                $app->make(WhatsAppNotificationService::class)
            );
        });
    }
}