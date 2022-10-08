<?php

namespace App\Repository;

use App\Entity\Detached\Search\Composition\PhraseSort;
use App\Entity\ProductSection;
use App\Pagination\Pagination;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @method ProductSection|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductSection|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductSection[]    findAll()
 * @method ProductSection[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductSectionRepository extends ServiceEntityRepository
{
    private RequestStack $requestStack;

    public function __construct(ManagerRegistry $registry, RequestStack $requestStack)
    {
        parent::__construct($registry, ProductSection::class);

        $this->requestStack = $requestStack;
    }

    public function findAllVisible()
    {
        return $this->createQueryBuilder('ps')
            ->andWhere('ps.isHidden = false')
            ->andWhere('NOT (ps.availableSince IS NOT NULL AND ps.availableSince > CURRENT_TIME())')
            ->orderBy('ps.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getSearchPagination(PhraseSort $searchData): Pagination
    {
        $sortData = $searchData->getSort()->getDqlSortData();

        $query = $this->createQueryBuilder('ps')

            //podminky
            ->orWhere('ps.name LIKE :name')
            ->setParameter('name', '%' . $searchData->getPhrase()->getText() . '%')

            ->orWhere('ps.slug LIKE :slug')
            ->setParameter('slug', '%' . $searchData->getPhrase()->getText() . '%')

            //razeni
            ->orderBy('ps.' . $sortData['attribute'], $sortData['order'])
            ->getQuery()
        ;

        return new Pagination($query, $this->requestStack->getCurrentRequest());
    }
}
