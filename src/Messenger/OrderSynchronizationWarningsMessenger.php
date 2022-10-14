<?php

namespace App\Messenger;

/**
 * Kolekce varování vzniklých při synchronizaci objednávky.
 *
 * @package App\Messenger
 */
class OrderSynchronizationWarningsMessenger
{
    private array $warnings = [];

    private string $messagePrefix;

    public function __construct(string $messagePrefix = '')
    {
        $this->messagePrefix = $messagePrefix;
    }

    public function addWarning(string $key, string $text): self
    {
        $this->warnings[$key] = $text;

        return $this;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function getMessagePrefix(): string
    {
        return $this->messagePrefix;
    }
}