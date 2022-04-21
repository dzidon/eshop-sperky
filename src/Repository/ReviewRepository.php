<?php

namespace App\Repository;

use App\Entity\Review;
use App\Pagination\Pagination;
use App\Service\SortingService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @method Review|null find($id, $lockMode = null, $lockVersion = null)
 * @method Review|null findOneBy(array $criteria, array $orderBy = null)
 * @method Review[]    findAll()
 * @method Review[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReviewRepository extends ServiceEntityRepository
{
    private SortingService $sorting;
    private $request;

    public function __construct(ManagerRegistry $registry, SortingService $sorting, RequestStack $requestStack)
    {
        parent::__construct($registry, Review::class);

        $this->sorting = $sorting;
        $this->request = $requestStack->getCurrentRequest();
    }

    public function getSearchPagination($searchPhrase = null, string $sortAttribute = null): Pagination
    {
        $sortData = $this->sorting->createSortData($sortAttribute, Review::getSortData());

        $queryBuilder = $this->createQueryBuilder('r')
            ->select('r', 'u')
            ->innerJoin('r.user', 'u')

            //vyhledavani
            ->andWhere('r.text LIKE :searchPhrase OR
                        CONCAT(u.nameFirst, \' \', u.nameLast) LIKE :searchPhrase')
            ->setParameter('searchPhrase', '%' . $searchPhrase . '%')

            //razeni
            ->orderBy('r.' . $sortData['attribute'], $sortData['order'])
        ;

        $query = $this->addVisibilityConditions($queryBuilder)
            ->getQuery()
        ;

        return new Pagination($query, $this->request);
    }

    public function findLatest(int $count)
    {
        $queryBuilder = $this->createQueryBuilder('r')
            ->select('r', 'u')
            ->innerJoin('r.user', 'u')
            ->setMaxResults($count)
            ->orderBy('r.created', 'DESC')
        ;

        return $this->addVisibilityConditions($queryBuilder)
            ->getQuery()
            ->getResult()
        ;
    }

    public function getTotalAndAverage()
    {
        $queryBuilder = $this->createQueryBuilder('r')
            ->select('count(r.id) as total, avg(r.stars) as average')
            ->innerJoin('r.user', 'u');

        return $this->addVisibilityConditions($queryBuilder)
            ->getQuery()
            ->getScalarResult()[0];
    }

    private function addVisibilityConditions(QueryBuilder $queryBuilder): QueryBuilder
    {
        $queryBuilder
            ->andWhere('u.nameFirst IS NOT NULL')
            ->andWhere('u.nameLast IS NOT NULL')
            ->andWhere('u.isMuted = 0');

        return $queryBuilder;
    }
}
