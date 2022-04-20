<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use App\Service\SortingService;
use DateTime;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Uid\Uuid;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Order|null find($id, $lockMode = null, $lockVersion = null)
 * @method Order|null findOneBy(array $criteria, array $orderBy = null)
 * @method Order[]    findAll()
 * @method Order[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderRepository extends ServiceEntityRepository
{
    private SortingService $sorting;

    public function __construct(ManagerRegistry $registry, SortingService $sorting)
    {
        parent::__construct($registry, Order::class);

        $this->sorting = $sorting;
    }

    public function getQueryForProfileSearchAndPagination(string $email, User $user, $searchPhrase = null, string $sortAttribute = null, int $lifecycle = null): Query
    {
        $sortData = $this->sorting->createSortData($sortAttribute, Order::getSortData());

        $queryBuilder = $this->createQueryBuilder('o')
            ->andWhere('o.email = :email OR o.user = :user')
            ->andWhere('o.lifecycleChapter > :lifecycleFresh')
            ->setParameter('email', $email)
            ->setParameter('user', $user)
            ->setParameter('lifecycleFresh', Order::LIFECYCLE_FRESH)

            // vyhledavani
            ->andWhere('o.id LIKE :searchPhrase')
            ->setParameter('searchPhrase', '%' . $searchPhrase . '%')
        ;

        if ($lifecycle !== null)
        {
            $queryBuilder
                // lifecycle
                ->andWhere('o.lifecycleChapter = :lifecycle')
                ->setParameter('lifecycle', $lifecycle)
            ;
        }

        return $queryBuilder
            // razeni
            ->orderBy('o.' . $sortData['attribute'], $sortData['order'])
            ->getQuery()
        ;
    }

    public function getQueryForAdminSearchAndPagination(string $searchPhrase = null, string $sortAttribute = null, int $lifecycle = null): Query
    {
        $sortData = $this->sorting->createSortData($sortAttribute, Order::getSortData());

        $queryBuilder = $this->createQueryBuilder('o')
            ->andWhere('o.lifecycleChapter > :lifecycleFresh OR (o.createdManually = true AND o.lifecycleChapter = :lifecycleFresh)')
            ->setParameter('lifecycleFresh', Order::LIFECYCLE_FRESH)

            // vyhledavani
            ->andWhere('o.id LIKE :searchPhrase')
            ->setParameter('searchPhrase', '%' . $searchPhrase . '%')
        ;

        if ($lifecycle !== null)
        {
            $queryBuilder
                // lifecycle
                ->andWhere('o.lifecycleChapter = :lifecycle')
                ->setParameter('lifecycle', $lifecycle)
            ;
        }

        return $queryBuilder
            // razeni
            ->orderBy('o.' . $sortData['attribute'], $sortData['order'])
            ->getQuery()
        ;
    }

    public function findOneAndFetchEverything(Uuid $token)
    {
        // objednávka, její doručovací a platební metoda (1 nebo žádný řádek)
        /** @var Order|null $order */
        $order = $this->createQueryBuilder('o')
            ->select('o, dm, pm')
            ->leftJoin('o.deliveryMethod', 'dm')
            ->leftJoin('o.paymentMethod', 'pm')
            ->andWhere('o.token = :token')
            ->setParameter('token', $token, 'uuid')
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if ($order === null)
        {
            return null;
        }

        // výskyty v košíku a ke každému jeho produkt
        $this->createQueryBuilder('o')
            ->select('PARTIAL o.{id}, oc, ocp')
            ->leftJoin('o.cartOccurences', 'oc')
            ->leftJoin('oc.product', 'ocp')
            ->andWhere('o.token = :token')
            ->setParameter('token', $token, 'uuid')
            ->getQuery()
            ->getResult()
        ;

        $cartOccurenceIds = [];
        $productIds = [];
        foreach ($order->getCartOccurences() as $cartOccurence)
        {
            $cartOccurenceIds[] = $cartOccurence->getId();

            /** @var Product|null $product */
            $product = $cartOccurence->getProduct();
            if ($product !== null)
            {
                $productIds[] = $product->getId();
            }
        }

        // ke každému výskytu v košíku jeho options
        $this->_em->createQuery('
            SELECT PARTIAL
                oc.{id}, oco, ocop
            FROM 
                App\Entity\CartOccurence oc
            LEFT JOIN
                oc.options oco
            LEFT JOIN
                oco.productOptionGroup ocop
            WHERE
                oc.id IN (:ids)
        ')
        ->setParameter('ids', $cartOccurenceIds)
        ->getResult();

        // ke každému produktu jeho skupiny options
        $this->_em->createQuery('
            SELECT PARTIAL
                ocp.{id}, ocpo
            FROM 
                App\Entity\Product ocp
            LEFT JOIN
                ocp.optionGroups ocpo
            WHERE
                ocp.id IN (:ids)
        ')
        ->setParameter('ids', $productIds)
        ->getResult();

        return $order;
    }

    public function findOneForPublicOverview(Uuid $token)
    {
        $order = $this->getOverviewQueryBuilder()
            ->andWhere('o.token = :token')
            ->setParameter('token', $token, 'uuid')
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if ($order === null)
        {
            return null;
        }

        $this->partialllyLoadCartOccurences($order);

        return $order;
    }

    public function findOneForProfileOverview(int $id, string $email, User $user)
    {
        $order = $this->getOverviewQueryBuilder()
            ->andWhere('o.id = :id')
            ->andWhere('o.email = :email OR o.user = :user')
            ->setParameter('id', $id)
            ->setParameter('email', $email)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if ($order === null)
        {
            return null;
        }

        $this->partialllyLoadCartOccurences($order);

        return $order;
    }

    public function findOneForAdminEdit(int $id)
    {
        $order = $this->getOverviewQueryBuilder() // <- o.lifecycleChapter > :lifecycleFresh
            ->andWhere('o.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if ($order === null)
        {
            return null;
        }

        $this->partialllyLoadCartOccurences($order);

        return $order;
    }

    public function findOneForAdminCancellation(int $id)
    {
        $order = $this->createQueryBuilder('o')
            ->andWhere('o.id = :id')
            ->andWhere('o.lifecycleChapter > :lifecycleFresh')
            ->setParameter('id', $id)
            ->setParameter('lifecycleFresh', Order::LIFECYCLE_FRESH)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if ($order === null)
        {
            return null;
        }

        $this->partialllyLoadCartOccurences($order);

        return $order;
    }

    public function findOneForAdminCustomEdit(int $id)
    {
        $order = $this->createQueryBuilder('o')
            ->andWhere('o.id = :id')
            ->andWhere('o.lifecycleChapter = :lifecycleFresh')
            ->andWhere('o.createdManually = true')
            ->setParameter('id', $id)
            ->setParameter('lifecycleFresh', Order::LIFECYCLE_FRESH)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if ($order === null)
        {
            return null;
        }

        $this->partialllyLoadCartOccurences($order);

        return $order;
    }

    public function deleteInactiveCartOrders()
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.createdManually = false')
            ->andWhere('o.lifecycleChapter = :lifecycleCart')
            ->andWhere('o.expireAt IS NULL OR o.expireAt <= :now')
            ->setParameter('lifecycleCart', Order::LIFECYCLE_FRESH)
            ->setParameter('now', new DateTime('now'))
            ->delete()
            ->getQuery()
            ->execute()
        ;
    }

    public function getCartTotalQuantity(Uuid $token)
    {
        return $this->createQueryBuilder('o')
            ->select('sum(oc.quantity) as quantity')
            ->leftJoin('o.cartOccurences', 'oc')
            ->andWhere('o.token = :token')
            ->andWhere('o.createdManually = false')
            ->andWhere('o.lifecycleChapter = :lifecycleCart')
            ->setParameter('token', $token, 'uuid')
            ->setParameter('lifecycleCart', Order::LIFECYCLE_FRESH)
            ->getQuery()
            ->getScalarResult()[0]
        ;
    }

    private function getOverviewQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('o')
            ->select('o, dm, pm')
            ->leftJoin('o.deliveryMethod', 'dm')
            ->leftJoin('o.paymentMethod', 'pm')
            ->andWhere('o.lifecycleChapter > :lifecycleFresh')
            ->setParameter('lifecycleFresh', Order::LIFECYCLE_FRESH)
        ;
    }

    private function partialllyLoadCartOccurences(Order $order): void
    {
        $this->createQueryBuilder('o')
            ->select('PARTIAL o.{id}, oc, ocp')
            ->leftJoin('o.cartOccurences', 'oc')
            ->leftJoin('oc.product', 'ocp')
            ->andWhere('o.id = :id')
            ->setParameter('id', $order->getId())
            ->getQuery()
            ->getResult()
        ;
    }
}