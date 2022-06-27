<?php

namespace App\Service;

use App\Entity\CartOccurence;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\ProductOption;
use App\EntityCollectionManagement\EntityCollectionEnvelope;
use App\Exception\CartException;
use App\OrderSynchronizer\OrderCartSynchronizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Cookie;
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

    /**
     * @var Cookie|null
     */
    private $newOrderCookie = null;

    /**
     * @var bool
     */
    private bool $isOrderNew = false;

    /**
     * @var int
     */
    private int $totalQuantityForNavbar = 0;

    private RequestStack $requestStack;
    private EntityManagerInterface $entityManager;
    private OrderCartSynchronizer $synchronizer;
    private EntityCollectionService $entityCollectionService;

    public function __construct(EntityManagerInterface $entityManager, RequestStack $requestStack, OrderCartSynchronizer $synchronizer, EntityCollectionService $entityCollectionService)
    {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
        $this->synchronizer = $synchronizer;
        $this->entityCollectionService = $entityCollectionService;
    }

    /**
     * @return int
     */
    public function getTotalQuantityForNavbar(): int
    {
        return $this->totalQuantityForNavbar;
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

    /**
     * @return Cookie|null
     */
    public function getNewOrderCookie(): ?Cookie
    {
        return $this->newOrderCookie;
    }

    /**
     * Vrátí token aktivní objednávky jako string
     *
     * @return string
     */
    public function getOrderToken(): string
    {
        return (string) $this->order->getToken();
    }

    /**
     * Tato metoda se volá jako první před vyvoláním každé controllerové akce.
     * Buď zajístí existenci aktivní objednávky nebo jen zjistí počet produktů v košíku
     * pro zobrazení v navigaci.
     *
     * @return $this
     */
    public function load(): self
    {
        $request = $this->requestStack->getCurrentRequest();
        $tokenInCookie = (string) $request->cookies->get(self::COOKIE_NAME);
        $tokenIsValid = UUid::isValid($tokenInCookie);

        $currentRoute = $request->attributes->get('_route');
        $loadFully = isset(OrderCartSynchronizer::SYNCHRONIZATION_ROUTES[$currentRoute]);

        if ($loadFully)
        {
            if ($tokenIsValid)
            {
                $uuid = Uuid::fromString($tokenInCookie);
                $this->order = $this->entityManager->getRepository(Order::class)->findOneAndFetchEverything($uuid);

                if ($this->order === null || $this->order->isCreatedManually() || $this->order->getLifecycleChapter() > Order::LIFECYCLE_FRESH)
                {
                    $this->createNewOrder();
                }
            }
            else
            {
                $this->createNewOrder();
            }

            $cartOccurencesEnvelope = new EntityCollectionEnvelope($this->order, [
                ['getterForCollection' => 'getCartOccurences', 'getterForParent' => 'getOrder'],
            ]);
            $this->synchronizer->synchronizeAndAddWarningsToFlashBag($this->order);

            $this->totalQuantityForNavbar = $this->order->getTotalQuantity();
            $this->obtainNewOrderCookie();

            if ($this->isOrderNew || $this->order->hasSynchronizationWarnings())
            {
                $this->entityCollectionService->removeOrphans($cartOccurencesEnvelope);
                $this->entityManager->persist($this->order);
                $this->entityManager->flush();
            }
        }
        else if ($tokenIsValid)
        {
            $uuid = Uuid::fromString($tokenInCookie);
            $result = $this->entityManager->getRepository(Order::class)->getCartTotalQuantity($uuid);

            if (isset($result['quantity']) && $result['quantity'] !== null)
            {
                $this->totalQuantityForNavbar = (int) $result['quantity'];
            }
        }

        return $this;
    }

    /**
     * Vloží produkt do košíku s požadovaným množstvím a volbami.
     *
     * @param Product $submittedProduct
     * @param int $submittedQuantity
     * @param array $submittedOptions
     * @throws CartException
     *
     * @return $this
     */
    public function insertProduct(Product $submittedProduct, int $submittedQuantity, array $submittedOptions): self
    {
        $inventory = $submittedProduct->getInventory();
        $requiredQuantity = $submittedQuantity;
        $targetCartOccurence = null;

        foreach ($this->order->getCartOccurences() as $cartOccurence)
        {
            $cartOccurenceProduct = $cartOccurence->getProduct();
            if($submittedProduct === $cartOccurenceProduct)
            {
                // zjišťování celkového počtu kusů produktu v košíku
                $requiredQuantity += $cartOccurence->getQuantity();

                // snaha najít v košíku vkládaný produkt s danými volbami
                $cartOccurenceOptions = $cartOccurence->getOptions()->toArray();
                if($targetCartOccurence === null && count($submittedOptions) === count($cartOccurenceOptions))
                {
                    $containsSubmittedOptions = ([] === array_udiff($submittedOptions, $cartOccurenceOptions,
                        function (ProductOption $objA, ProductOption $objB) {
                            return $objA->getId() - $objB->getId();
                        }
                    ));

                    if($containsSubmittedOptions)
                    {
                        $targetCartOccurence = $cartOccurence;
                    }
                }
            }
        }

        if($requiredQuantity > $inventory)
        {
            throw new CartException('Tolik kusů už na skladě bohužel nemáme.');
        }

        if($targetCartOccurence === null)
        {
            $targetCartOccurence = new CartOccurence();
            $targetCartOccurence
                ->setOrder($this->order)
                ->setProduct($submittedProduct)
                ->setQuantity($submittedQuantity)
                ->setName($submittedProduct->getName())
                ->setPriceWithoutVat($submittedProduct->getPriceWithoutVat())
                ->setPriceWithVat($submittedProduct->getPriceWithVat())
            ;

            foreach ($submittedOptions as $submittedOption)
            {
                $targetCartOccurence->addOption($submittedOption);
            }
            $targetCartOccurence->generateOptionsString();
            $this->order->addCartOccurence($targetCartOccurence);
        }
        else
        {
            $targetCartOccurence->addQuantity($submittedQuantity);
        }

        $this->entityManager->persist($targetCartOccurence);
        $this->entityManager->flush();

        return $this;
    }

    /**
     * Aktualizuje počty produktů v košíku. Pokud je nějaký počet menší nebo roven 0, CartOccurence se odstraní. Pokud je
     * počet větší než 0, CartOccurence se uloží.
     *
     * @return $this
     */
    public function updateQuantities(): self
    {
        foreach ($this->order->getCartOccurences() as $cartOccurence)
        {
            if($cartOccurence->getQuantity() <= 0)
            {
                $this->order->removeCartOccurence($cartOccurence);
                $this->entityManager->remove($cartOccurence);
            }
            else
            {
                $this->entityManager->persist($cartOccurence);
            }
        }

        $this->entityManager->flush();
        $this->order->reindexCartOccurences();

        return $this;
    }

    /**
     * Odstraní CartOccurence z košíku
     *
     * @param int|null $cartOccurenceId
     * @throws CartException
     *
     * @return $this
     */
    public function removeCartOccurence(?int $cartOccurenceId): self
    {
        if($cartOccurenceId === null)
        {
            throw new CartException('Byl zadán neplatný produkt.');
        }

        $found = false;
        foreach ($this->order->getCartOccurences() as $cartOccurence)
        {
            if($cartOccurence->getId() === $cartOccurenceId)
            {
                $found = true;
                $this->order->removeCartOccurence($cartOccurence);
                $this->entityManager->remove($cartOccurence);
                break;
            }
        }

        if(!$found)
        {
            throw new CartException('Tento produkt v košíku nemáte.');
        }

        $this->entityManager->flush();
        $this->order->reindexCartOccurences();

        return $this;
    }

    /**
     * Vrátí novou cookie s tokenem aktivní objednávky, pokud je objednávka nová, nebo se blíží k expiraci.
     */
    private function obtainNewOrderCookie(): void
    {
        $this->newOrderCookie = null;

        if ($this->isOrderNew || (($this->order->getExpireAt()->getTimestamp() - time()) < (86400 * Order::REFRESH_WINDOW_IN_DAYS))) // 86400s = 1d
        {
            $this->order->setExpireAtBasedOnLifetime();
            $expires = time() + (86400 * Order::LIFETIME_IN_DAYS);
            $token = $this->getOrderToken();

            $this->newOrderCookie = (new Cookie(self::COOKIE_NAME))
                ->withValue($token)
                ->withExpires($expires)
                ->withSecure(true)
                ->withHttpOnly()
            ;
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
}