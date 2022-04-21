<?php

namespace App\Service;

use App\Entity\Order;
use App\Service\OrderSynchronizer\CustomOrderSynchronizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Třída řešící načítání objednávky podle tokenu vytvořené na míru
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

    public function getSynchronizer(): CustomOrderSynchronizer
    {
        return $this->synchronizer;
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
            $order = $this->entityManager->getRepository(Order::class)->findOneAndFetchEverything($uuid);
            if ($order !== null && $order->isCreatedManually() && $order->getLifecycleChapter() === Order::LIFECYCLE_FRESH)
            {
                $this->order = $order;

                $this->synchronizer->synchronize($this->order);
                $this->synchronizer->addWarningsToFlashBag();

                $this->order->calculateTotals();

                if ($this->synchronizer->hasWarnings())
                {
                    $this->entityManager->persist($this->order);
                    $this->entityManager->flush();
                }
            }
        }

        return $this->order;
    }
}