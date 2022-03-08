<?php

namespace App\Repository;

use App\Entity\ProductSection;
use App\Service\SortingService;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProductSection|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductSection|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductSection[]    findAll()
 * @method ProductSection[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductSectionRepository extends ServiceEntityRepository
{
    private SortingService $sorting;

    public function __construct(ManagerRegistry $registry, SortingService $sorting)
    {
        parent::__construct($registry, ProductSection::class);

        $this->sorting = $sorting;
    }

    public function findAllVisible()
    {
        return $this->createQueryBuilder('ps')
            ->andWhere('ps.isHidden = false')
            ->andWhere('NOT (ps.availableSince IS NOT NULL AND ps.availableSince > :now)')
            ->setParameter('now', new DateTime('now'))
            ->orderBy('ps.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getQueryForSearchAndPagination($searchPhrase = null, string $sortAttribute = null): Query
    {
        $sortData = $this->sorting->createSortData($sortAttribute, ProductSection::getSortData());

        return $this->createQueryBuilder('ps')

            //podminky
            ->orWhere('ps.name LIKE :name')
            ->setParameter('name', '%' . $searchPhrase . '%')

            ->orWhere('ps.slug LIKE :slug')
            ->setParameter('slug', '%' . $searchPhrase . '%')

            //razeni
            ->orderBy('ps.' . $sortData['attribute'], $sortData['order'])
            ->getQuery()
        ;
    }
}
