<?php

namespace App\Repository;

use App\Entity\ProductInformationGroup;
use App\Pagination\Pagination;
use App\Service\SortingService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @method ProductInformationGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductInformationGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductInformationGroup[]    findAll()
 * @method ProductInformationGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductInformationGroupRepository extends ServiceEntityRepository
{
    private SortingService $sorting;
    private $request;

    public function __construct(ManagerRegistry $registry, SortingService $sorting, RequestStack $requestStack)
    {
        parent::__construct($registry, ProductInformationGroup::class);

        $this->sorting = $sorting;
        $this->request = $requestStack->getCurrentRequest();
    }

    public function getSearchPagination($searchPhrase = null, string $sortAttribute = null): Pagination
    {
        $sortData = $this->sorting->createSortData($sortAttribute, ProductInformationGroup::getSortData());

        $query = $this->createQueryBuilder('pig')

            //podminky
            ->orWhere('pig.name LIKE :name')
            ->setParameter('name', '%' . $searchPhrase . '%')

            //razeni
            ->orderBy('pig.' . $sortData['attribute'], $sortData['order'])
            ->getQuery()
        ;

        return new Pagination($query, $this->request);
    }

    public function getArrayOfNames(): array
    {
        $arrayOfAllData = $this->createQueryBuilder('pig', 'pig.name')
            ->getQuery()
            ->getArrayResult();

        return array_keys($arrayOfAllData); // chceme jen názvy, ty jsou v klíčích
    }
}