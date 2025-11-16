<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $document;
    public $userId;
    public $submissionType;
    public $metadata;

    /**
     * Create a new event instance.
     */
    public function __construct($document, $userId = null, string $submissionType = 'general', array $metadata = [])
    {
        $this->document = $document;
        $this->userId = $userId;
        $this->submissionType = $submissionType;
        $this->metadata = $metadata;
    }
}
