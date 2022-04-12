<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\Product;
use Doctrine\ORM\Query;
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
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
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
            ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, 1)
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
        ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, 1)
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
        ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, 1)
        ->getResult();

        return $order;
    }

    public function getCartTotalQuantity(Uuid $token)
    {
        return $this->createQueryBuilder('o')
            ->select('sum(oc.quantity) as quantity')
            ->leftJoin('o.cartOccurences', 'oc')
            ->andWhere('o.token = :token')
            ->andWhere('o.createdManually = false')
            ->andWhere('o.finished = false')
            ->setParameter('token', $token, 'uuid')
            ->getQuery()
            ->getScalarResult()[0]
        ;
    }
}