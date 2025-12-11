<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $document;
    public $userId;
    public $metadata;

    /**
     * Create a new event instance.
     */
    public function __construct($document, $userId = null, array $metadata = [])
    {
        $this->document = $document;
        $this->userId = $userId;
        $this->metadata = $metadata;
    }
}
