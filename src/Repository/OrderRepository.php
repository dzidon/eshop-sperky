<?php

namespace App\Repository;

use App\Entity\Order;
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

    public function findOneAndFetchCartOccurences(Uuid $token)
    {
        $results = $this->createQueryBuilder('o')
            ->select('o, oc, ocp, ocpp, oco, ocop')
            ->leftJoin('o.cartOccurences', 'oc')
            ->leftJoin('oc.product', 'ocp')
            ->leftJoin('ocp.optionGroups', 'ocpp')
            ->leftJoin('oc.options', 'oco')
            ->leftJoin('oco.productOptionGroup', 'ocop')
            ->andWhere('o.token = :token')
            ->setParameter('token', $token, 'uuid')
            ->getQuery()
            ->getResult()
        ;

        if(isset($results[0]))
        {
            return $results[0];
        }

        return null;
    }
}