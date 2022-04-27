<?php

namespace App\OrderSynchronizer;

/**
 * Synchronizuje stav objednávky na míru
 *
 * @package App\OrderSynchronizer
 */
class CustomOrderSynchronizer extends AbstractOrderSynchronizer
{
    /**
     * {@inheritdoc}
     */
    protected static function getWarningPrefix(): string
    {
        return 'Ve vaší objednávce na míru došlo ke změně: ';
    }
}