<?php

namespace App\Service\OrderSynchronizer;

use App\Entity\Order;
use LogicException;

/**
 * Abstraktní třída pro synchronizátory, které zajišťují aktuálnost stavu objednávky.
 *
 * @package App\Utils\OrderSynchronizer
 */
abstract class AbstractOrderSynchronizer
{
    protected bool $hasWarnings = false;
    private array $warnings = [];
    protected Order $order;

    /**
     * Nastaví objednávku, jejíž stav se má synchronizovat.
     *
     * @param Order $order
     */
    public function setOrder(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Vrátí várování, která vznikla při synchronizaci.
     *
     * @return array
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Vrátí true, pokud při synchronizaci vznikla nějaká varování.
     *
     * @return bool
     */
    public function hasWarnings(): bool
    {
        return $this->hasWarnings;
    }

    /**
     * Synchronizuje stav objednávky.
     */
    public function synchronize(): void
    {
        if($this->order === null)
        {
            throw new LogicException( sprintf('%s nedostal objednávku přes setOrder.', static::class) );
        }
    }

    /**
     * Přidá varování vzniklé během synchronizace.
     *
     * @param string $warningKey
     * @param string $warningText
     * @return $this
     */
    protected function addWarning(string $warningKey, string $warningText): self
    {
        $this->warnings[$warningKey] = static::getWarningPrefix() . $warningText;
        $this->hasWarnings = true;

        return $this;
    }

    /**
     * Vrátí prefix pro varování vzniklé při synchronizaci.
     *
     * @return string
     */
    abstract protected static function getWarningPrefix(): string;
}