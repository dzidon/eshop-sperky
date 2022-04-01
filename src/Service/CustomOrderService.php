<?php

namespace App\Service;

use App\Entity\Order;
use App\Service\OrderSynchronizer\CustomOrderSynchronizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Třída řešící načítání objednávky vytvořené na míru
 *
 * @package App\Service
 */
class CustomOrderService
{
    /**
     * @var Order|null
     */
    private $order = null;

    private CustomOrderSynchronizer $synchronizer;
    private EntityManagerInterface $entityManager;

    public function __construct(CustomOrderSynchronizer $synchronizer, EntityManagerInterface $entityManager)
    {
        $this->synchronizer = $synchronizer;
        $this->entityManager = $entityManager;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    /**
     * Načte objednávku vytvořenou na míru podle tokenu.
     *
     * @param string|null $token
     * @return Order|null
     */
    public function loadCustomOrder(?string $token): ?Order
    {
        if ($token !== null && UUid::isValid($token))
        {
            $uuid = Uuid::fromString($token);

            /** @var Order|null $order */
            $order = $this->entityManager->getRepository(Order::class)->findOneAndFetchCartOccurences($uuid);
            if ($order !== null && $order->isCreatedManually() && !$order->isFinished())
            {
                $this->order = $order;

                $this->synchronizer
                    ->setOrder($this->order)
                    ->synchronize();

                $this->synchronizer->addWarningsToFlashBag();

                $this->order->calculateTotals();
            }
        }

        return $this->order;
    }
}