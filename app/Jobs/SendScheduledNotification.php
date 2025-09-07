<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendScheduledNotification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user,
        public array $notificationData
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $notificationService = app(NotificationService::class);
        $notificationService->sendNotification($this->user, $this->notificationData);
    }
}
