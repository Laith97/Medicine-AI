<?php

namespace App\Services;

interface WhatsAppProviderInterface
{
    public function __construct(array $config);
    public function sendMessage(string $to, string $message): array;
    public function validateConfig(): bool;
    public function getName(): string;
    public function getKey(): string;
}