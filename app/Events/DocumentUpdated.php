<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $document;
    public $userId;
    public $changes;
    public $metadata;

    /**
     * Create a new event instance.
     */
    public function __construct($document, $userId = null, array $changes = [], array $metadata = [])
    {
        $this->document = $document;
        $this->userId = $userId;
        $this->changes = $changes;
        $this->metadata = $metadata;
    }
}
