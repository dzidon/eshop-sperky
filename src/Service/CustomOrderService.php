<?php

namespace App\Service;

use App\Entity\Order;
use App\OrderSynchronizer\CustomOrderSynchronizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Uuid;

/**
 * Třída řešící načítání objednávky podle tokenu vytvořené na míru
 *
 * @package App\Service
 */
class CustomOrderService
{
    private RequestStack $requestStack;
    private CustomOrderSynchronizer $synchronizer;
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager, RequestStack $requestStack)
    {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;

        $this->synchronizer = new CustomOrderSynchronizer();
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
        $order = null;
        if ($token !== null && UUid::isValid($token))
        {
            $uuid = Uuid::fromString($token);

            /** @var Order|null $order */
            $order = $this->entityManager->getRepository(Order::class)->findOneAndFetchEverything($uuid);
            if ($order !== null && $order->isCreatedManually() && $order->getLifecycleChapter() === Order::LIFECYCLE_FRESH)
            {
                $this->synchronizer->synchronize($order);
                $this->synchronizer->addWarningsToFlashBag($this->requestStack->getCurrentRequest());

                $order->calculateTotals();

                if ($this->synchronizer->hasWarnings())
                {
                    $this->entityManager->persist($order);
                    $this->entityManager->flush();
                }
            }
        }

        return $order;
    }
}