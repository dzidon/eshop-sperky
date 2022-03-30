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
    private float $totalPriceWithoutVat = 0.0;
    private float $totalPriceWithVat = 0.0;

    private CustomOrderSynchronizer $synchronizer;
    private EntityManagerInterface $entityManager;

    public function __construct(CustomOrderSynchronizer $synchronizer, EntityManagerInterface $entityManager)
    {
        $this->synchronizer = $synchronizer;
        $this->entityManager = $entityManager;
    }

    public function getTotalPriceWithVat(): float
    {
        return $this->totalPriceWithVat;
    }

    public function getTotalPriceWithoutVat(): float
    {
        return $this->totalPriceWithoutVat;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    /**
     * Načte objednávku vytvořenou na míru podle tokenu.
     *
     * @param string|null $token
     */
    public function loadCustomOrder(?string $token): void
    {
        if ($token !== null && UUid::isValid($token))
        {
            $uuid = Uuid::fromString($token);

            /** @var Order|null $order */
            $order = $this->entityManager->getRepository(Order::class)->findOneBy(['token' => $uuid]);
            if ($order !== null && $order->isCreatedManually() && !$order->isFinished())
            {
                $this->order = $order;

                $this->synchronizer
                    ->setOrder($this->order)
                    ->synchronize();

                $this->synchronizer->addWarningsToFlashBag();

                $this->calculateTotals();
            }
        }
    }

    /**
     * Spočítá celkovou cenu bez DPH a celkovou cenu s DPH.
     */
    private function calculateTotals(): void
    {
        $this->totalPriceWithVat = 0.0;
        $this->totalPriceWithoutVat = 0.0;

        foreach ($this->order->getCartOccurences() as $cartOccurence)
        {
            $this->totalPriceWithVat += $cartOccurence->getQuantity() * $cartOccurence->getPriceWithVat();
            $this->totalPriceWithoutVat += $cartOccurence->getQuantity() * $cartOccurence->getPriceWithoutVat();
        }
    }
}