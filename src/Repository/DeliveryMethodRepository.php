<?php

namespace App\Repository;

use App\Entity\DeliveryMethod;
use App\Entity\Detached\Search\Composition\PhraseSort;
use App\Pagination\Pagination;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @method DeliveryMethod|null find($id, $lockMode = null, $lockVersion = null)
 * @method DeliveryMethod|null findOneBy(array $criteria, array $orderBy = null)
 * @method DeliveryMethod[]    findAll()
 * @method DeliveryMethod[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DeliveryMethodRepository extends ServiceEntityRepository
{
    private RequestStack $requestStack;

    public function __construct(ManagerRegistry $registry, RequestStack $requestStack)
    {
        parent::__construct($registry, DeliveryMethod::class);

        $this->requestStack = $requestStack;
    }

    public function getSearchPagination(PhraseSort $searchData): Pagination
    {
        $sortData = $searchData->getSort()->getDqlSortData();

        $query = $this->createQueryBuilder('dm')

            //podminky
            ->andWhere('dm.name LIKE :name')
            ->setParameter('name', '%' . $searchData->getPhrase()->getText() . '%')

            //razeni
            ->orderBy('dm.' . $sortData['attribute'], $sortData['order'])
            ->getQuery()
        ;

        return new Pagination($query, $this->requestStack->getCurrentRequest());
    }
}