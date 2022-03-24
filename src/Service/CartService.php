<?php

namespace App\Service;

use App\Entity\CartOccurence;
use App\Entity\Order;
use App\Entity\Product;
use App\Exception\CartException;
use App\Utils\OrderSynchronizer\OrderCartSynchronizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Uuid;

/**
 * Třída řešící uživatelův nákupní košík a jeho aktivní objednávku
 *
 * @package App\Service
 */
class CartService
{
    const COOKIE_NAME = 'CARTTOKEN';

    /**
     * @var Order|null
     */
    private $order = null;

    private int $totalProducts = 0;
    private float $totalPriceWithoutVat = 0.0;
    private float $totalPriceWithVat = 0.0;
    private bool $hasSynchronizationWarnings = false;
    private bool $isOrderNew = false;

    /** @var Request */
    private $request;
    private EntityManagerInterface $entityManager;

    public function __construct(RequestStack $requestStack, EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->request = $requestStack->getCurrentRequest();

        $this->obtainCartOrder();
    }

    /**
     * Vrátí token aktivní objednávky jako string
     *
     * @return string
     */
    public function getToken(): string
    {
        return (string) $this->order->getToken();
    }

    /**
     * Vrátí aktivní objednávku
     *
     * @return Order
     */
    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getTotalProducts(): int
    {
        return $this->totalProducts;
    }

    public function getTotalPriceWithVat(): float
    {
        return $this->totalPriceWithVat;
    }

    public function getTotalPriceWithoutVat(): float
    {
        return $this->totalPriceWithoutVat;
    }

    public function hasSynchronizationWarnings(): bool
    {
        return $this->hasSynchronizationWarnings;
    }

    /**
     * Tato metoda se volá jako první před vyvoláním každé controllerové akce. Zajišťuje existenci aktivní objednávky.
     */
    public function obtainCartOrder(): void
    {
        $tokenInCookie = (string) $this->request->cookies->get(self::COOKIE_NAME);

        if (UUid::isValid($tokenInCookie))
        {
            $uuid = Uuid::fromString($tokenInCookie);

            /** @var Order|null $order */
            $this->order = $this->entityManager->getRepository(Order::class)->findOneAndFetchCartOccurences($uuid);
            if ($this->order === null || $this->order->isCreatedManually() || $this->order->isFinished())
            {
                $this->createNewOrder();
            }
            else
            {
                $this->synchronizeAndAddWarningsToFlashBag();
                $this->calculateTotals();
            }
        }
        else
        {
            $this->createNewOrder();
        }
    }

    /**
     * Pokud je aktivní objednávka nová, uloží se do DB a vrátí se cookie s daným tokenem. Pokud už je aktivní
     * objednávka uložená v DB, vrátí se null a nastaví se datum poslední aktivity.
     *
     * @return Cookie|null
     */
    public function getCookieAndSaveOrder()
    {
        if ($this->isOrderNew)
        {
            $this->orderPersistAndFlush();
        }
        // aby to při každém requestu nevolalo UPDATE, aktualizuje se datum expirace jen několik dní před expirací
        else if (($this->order->getExpireAt()->getTimestamp() - time()) < (86400 * Order::REFRESH_WINDOW_IN_DAYS))
        {
            $this->order->setExpireAtBasedOnLifetime();
            $this->orderPersistAndFlush();
        }
        else
        {
            return null;
        }

        $token = $this->getToken();
        $expires = time() + (86400 * Order::LIFETIME_IN_DAYS);

        return (new Cookie(self::COOKIE_NAME))
            ->withValue($token)
            ->withExpires($expires)
            ->withSecure(true)
            ->withHttpOnly()
        ;
    }

    /**
     * Vloží produkt do košíku s požadovaným množstvím a volbami.
     *
     * @param Product $submittedProduct
     * @param int $submittedQuantity
     * @param array $submittedOptions
     * @throws CartException
     */
    public function insertProduct(Product $submittedProduct, int $submittedQuantity, array $submittedOptions): void
    {
        $inventory = $submittedProduct->getInventory();
        $requiredQuantity = $submittedQuantity;
        $existingCartOccurence = null;

        foreach ($this->order->getCartOccurences() as $cartOccurence)
        {
            $cartOccurenceProduct = $cartOccurence->getProduct();
            if($submittedProduct === $cartOccurenceProduct)
            {
                // zjišťování celkového počtu kusů produktu v košíku
                $requiredQuantity += $cartOccurence->getQuantity();

                // snaha najít v košíku vkládaný produkt s danými volbami
                if($existingCartOccurence === null)
                {
                    $containsSubmittedOptions = ([] === array_udiff($cartOccurence->getOptions()->toArray(), $submittedOptions,
                        function ($objA, $objB) {
                            return $objA->getId() - $objB->getId();
                        }
                    ));

                    if($containsSubmittedOptions)
                    {
                        $existingCartOccurence = $cartOccurence;
                    }
                }
            }
        }

        if($requiredQuantity > $inventory)
        {
            throw new CartException('Tolik kusů už na skladě bohužel nemáme.');
        }

        if($existingCartOccurence === null)
        {
            $newCartOccurence = new CartOccurence();
            $newCartOccurence
                ->setOrder($this->order)
                ->setProduct($submittedProduct)
                ->setQuantity($submittedQuantity)
                ->setName($submittedProduct->getName())
                ->setPriceWithoutVat($submittedProduct->getPriceWithoutVat())
                ->setPriceWithVat($submittedProduct->getPriceWithVat());

            foreach ($submittedOptions as $submittedOption)
            {
                $newCartOccurence->addOption($submittedOption);
            }
            $this->order->addCartOccurence($newCartOccurence);
        }
        else
        {
            $existingCartOccurence->addQuantity($submittedQuantity);
        }

        $this->orderPersistAndFlush();
        $this->calculateTotals();
    }

    /**
     * Synchronizuje stav historických dat objednávky se stavem dat v ostatních entitách (např. ceny produktů).
     * Pokud při synchronizaci vznikla nějaká varování, přidají se do flashbagu.
     */
    private function synchronizeAndAddWarningsToFlashBag(): void
    {
        $synchronizer = new OrderCartSynchronizer($this->order);
        $synchronizer->synchronize();

        if($synchronizer->hasWarnings())
        {
            $this->hasSynchronizationWarnings = true;
            foreach ($synchronizer->getWarnings() as $warning)
            {
                $this->request->getSession()->getFlashBag()->add('warning', $warning);
            }
        }

        if($synchronizer->orderChanged())
        {
            $this->orderPersistAndFlush();
        }
    }

    /**
     * Spočítá celkový počet produktů, celkovou cenu bez DPH a celkovou cenu s DPH
     */
    private function calculateTotals(): void
    {
        $this->totalProducts = 0;
        $this->totalPriceWithVat = 0.0;
        $this->totalPriceWithoutVat = 0.0;

        foreach ($this->order->getCartOccurences() as $cartOccurence)
        {
            $this->totalProducts += $cartOccurence->getQuantity();
            $this->totalPriceWithVat += $cartOccurence->getQuantity() * $cartOccurence->getPriceWithVat();
            $this->totalPriceWithoutVat += $cartOccurence->getQuantity() * $cartOccurence->getPriceWithoutVat();
        }
    }

    /**
     * Vytvoří novou aktivní objednávku.
     */
    private function createNewOrder(): void
    {
        $this->order = new Order();
        $this->isOrderNew = true;
    }

    /**
     * Uloží aktivní objednávku do databáze
     */
    private function orderPersistAndFlush(): void
    {
        $this->entityManager->persist($this->order);
        $this->entityManager->flush();
    }
}