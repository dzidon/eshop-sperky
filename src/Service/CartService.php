<?php

namespace App\Service;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Uuid;

/**
 * Třída řešící nákupní košík
 *
 * @package App\Service
 */
class CartService
{
    const COOKIE_NAME = 'CARTTOKEN';

    private $order = null;

    /** @var Request */
    private $request;
    private EntityManagerInterface $entityManager;

    public function __construct(RequestStack $requestStack, EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->request = $requestStack->getCurrentRequest();

        $this->obtainOrder();
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

    /**
     * Pokud je aktivní objednávka nová, uloží se do DB a vrátí se cookie s daným tokenem. Pokud už je aktivní
     * objednávka uložená v DB, vrátí se null, protože není potřeba cookie nastavovat do odpovědi.
     *
     * @return Cookie|null
     */
    public function getCookieAndSaveOrder()
    {
        $this->entityManager->clear();

        if ($this->order->getId() === null)
        {
            $this->entityManager->persist($this->order);
            $this->entityManager->flush();

            $token = $this->getToken();
            $expires = time()+(60*60*24*30); // za měsíc

            return (new Cookie(self::COOKIE_NAME))
                ->withValue($token)
                ->withExpires($expires)
                ->withSecure(true)
                ->withHttpOnly()
            ;
        }

        return null;
    }

    /**
     * Tato metoda se volá v konstruktoru. Zajišťuje existenci aktivní objednávky.
     */
    private function obtainOrder(): void
    {
        $tokenInCookie = (string) $this->request->cookies->get(self::COOKIE_NAME);

        if (UUid::isValid($tokenInCookie))
        {
            $uuid = Uuid::fromString($tokenInCookie);

            /** @var Order|null $order */
            $this->order = $this->entityManager->getRepository(Order::class)->findOneBy(['token' => $uuid]);
            if ($this->order === null || !$this->order->isOpen())
            {
                $this->createNewOrder();
            }
        }
        else
        {
            $this->createNewOrder();
        }
    }

    /**
     * Vytvoří novou aktivní objednávku.
     */
    private function createNewOrder(): void
    {
        $this->order = new Order();
    }
}