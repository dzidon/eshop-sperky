<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Review;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Review|null find($id, $lockMode = null, $lockVersion = null)
 * @method Review|null findOneBy(array $criteria, array $orderBy = null)
 * @method Review[]    findAll()
 * @method Review[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    public function createNew(User $user): Review
    {
        $review = new Review();
        $now = new \DateTime('now');
        $review->setCreated($now)
               ->setUpdated($now)
               ->setUser($user);

        return $review;
    }

    public function getQueryForPagination(): Query
    {
        return $this->createQueryBuilder('r')
            ->select('r', 'u')
            ->innerJoin('r.user', 'u')
            ->orderBy('r.created', 'DESC')
            ->andWhere('u.nameFirst IS NOT NULL')
            ->andWhere('u.nameLast IS NOT NULL')
            ->getQuery();
    }

    public function findLatest(int $count)
    {
        return $this->getQueryForPagination()
            ->setMaxResults($count)
            ->getResult();
    }

    public function getTotalAndAverage()
    {
        return $this->createQueryBuilder('r')
            ->select('count(r.id) as total, avg(r.stars) as average')
            ->innerJoin('r.user', 'u')
            ->andWhere('u.nameFirst IS NOT NULL')
            ->andWhere('u.nameLast IS NOT NULL')
            ->getQuery()
            ->getScalarResult()[0];
    }
}
