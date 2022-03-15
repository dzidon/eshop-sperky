<?php

namespace App\Repository;

use App\Entity\Review;
use App\Service\SortingService;
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
    private SortingService $sorting;

    public function __construct(ManagerRegistry $registry, SortingService $sorting)
    {
        parent::__construct($registry, Review::class);

        $this->sorting = $sorting;
    }

    public function getQueryForSearchAndPagination($searchPhrase = null, string $sortAttribute = null): Query
    {
        $sortData = $this->sorting->createSortData($sortAttribute, Review::getSortData());

        return $this->createQueryBuilder('r')
            ->select('r', 'u')
            ->innerJoin('r.user', 'u')
            ->andWhere('u.nameFirst IS NOT NULL')
            ->andWhere('u.nameLast IS NOT NULL')
            ->andWhere('u.isMuted = 0')

            //vyhledavani
            ->andWhere('r.stars LIKE :searchPhrase OR
                        r.text LIKE :searchPhrase OR
                        CONCAT(u.nameFirst, \' \', u.nameLast) LIKE :searchPhrase')
            ->setParameter('searchPhrase', '%' . $searchPhrase . '%')

            //razeni
            ->orderBy('r.' . $sortData['attribute'], $sortData['order'])
            ->getQuery();
    }

    public function findLatest(int $count)
    {
        return $this->getQueryForSearchAndPagination()
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
            ->andWhere('u.isMuted = 0')
            ->getQuery()
            ->getScalarResult()[0];
    }
}
