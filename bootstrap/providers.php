<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    App\Providers\BroadcastServiceProvider::class,
    App\Providers\SmsServiceProvider::class,
    App\Providers\WhatsAppServiceProvider::class,
    OpenAI\Laravel\ServiceProvider::class,
];
