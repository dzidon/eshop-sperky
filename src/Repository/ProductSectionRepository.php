<?php

namespace App\Repository;

use App\Entity\ProductSection;
use App\Pagination\Pagination;
use App\Service\SortingService;
use DateTime;
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
    private SortingService $sorting;
    private $request;

    public function __construct(ManagerRegistry $registry, SortingService $sorting, RequestStack $requestStack)
    {
        parent::__construct($registry, ProductSection::class);

        $this->sorting = $sorting;
        $this->request = $requestStack->getCurrentRequest();
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

    public function getSearchPagination($searchPhrase = null, string $sortAttribute = null): Pagination
    {
        $sortData = $this->sorting->createSortData($sortAttribute, ProductSection::getSortData());

        $query = $this->createQueryBuilder('ps')

            //podminky
            ->orWhere('ps.name LIKE :name')
            ->setParameter('name', '%' . $searchPhrase . '%')

            ->orWhere('ps.slug LIKE :slug')
            ->setParameter('slug', '%' . $searchPhrase . '%')

            //razeni
            ->orderBy('ps.' . $sortData['attribute'], $sortData['order'])
            ->getQuery()
        ;

        return new Pagination($query, $this->request);
    }
}
