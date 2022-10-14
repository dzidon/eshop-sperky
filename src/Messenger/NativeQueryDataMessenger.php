<?php

namespace App\Messenger;

/**
 * Data pro sestavení SQL dotazu.
 *
 * @package App\Messenger
 */
class NativeQueryDataMessenger
{
    private string $clause;

    private array $placeholders;

    public function __construct(string $clause, array $placeholders)
    {
        $this->clause = $clause;
        $this->placeholders = $placeholders;
    }

    public function getClause(): string
    {
        return $this->clause;
    }

    public function getPlaceholders(): array
    {
        return $this->placeholders;
    }
}