<?php

namespace App\Utils\OrderSynchronizer;

use App\Entity\Order;

abstract class AbstractOrderSynchronizer
{
    protected bool $hasWarnings = false;
    protected bool $orderChanged = false;
    private array $warnings = [];
    protected Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function hasWarnings(): bool
    {
        return $this->hasWarnings;
    }

    public function orderChanged(): bool
    {
        return $this->orderChanged;
    }

    protected function addWarning(string $warning): self
    {
        $this->warnings[] = static::getWarningPrefix() . $warning;
        $this->hasWarnings = true;

        return $this;
    }

    abstract protected static function getWarningPrefix(): string;

    abstract public function synchronize(): void;
}