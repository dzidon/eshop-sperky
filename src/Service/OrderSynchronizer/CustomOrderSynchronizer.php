<?php

namespace App\Service\OrderSynchronizer;

/**
 * Synchronizuje stav objednávky na míru
 *
 * @package App\Service\OrderSynchronizer
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