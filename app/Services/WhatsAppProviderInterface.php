<?php

namespace App\Services;

interface WhatsAppProviderInterface
{
    public function __construct(array $config);
    public function sendMessage(string $to, string $message): bool;
    public function validateConfig(): bool;
}