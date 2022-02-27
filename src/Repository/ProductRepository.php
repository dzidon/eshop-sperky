<?php

namespace App\Repository;

use App\Entity\Product;
use App\Service\SortingService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    private SortingService $sorting;

    public function __construct(ManagerRegistry $registry, SortingService $sorting)
    {
        parent::__construct($registry, Product::class);

        $this->sorting = $sorting;
    }

    public function findOneByIdAndFetchEverything($id)
    {
        return $this->createQueryBuilder('p')
            ->select('p, ps, pc, po, pi, pig, pimg')
            ->leftJoin('p.section', 'ps')
            ->leftJoin('p.categories', 'pc')
            ->leftJoin('pc.productCategoryGroup', 'pcg')
            ->leftJoin('p.options', 'po')
            ->leftJoin('p.info', 'pi')
            ->leftJoin('pi.productInformationGroup', 'pig')
            ->leftJoin('p.images', 'pimg')
            ->andWhere('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function getQueryForSearchAndPagination(bool $inAdmin, $searchPhrase = null, string $sortAttribute = null): Query
    {
        $queryBuilder = $this->createQueryBuilder('p');

        if($inAdmin)
        {
            $sortData = $this->sorting->createSortData($sortAttribute, Product::getSortData()['admin']);

            $queryBuilder
                // vyhledavani
                ->andWhere('p.name LIKE :searchPhrase OR
                            p.slug LIKE :searchPhrase OR
                            p.priceWithoutVat LIKE :searchPhrase OR
                            p.priceWithVat LIKE :searchPhrase OR
                            p.vat LIKE :searchPhrase OR
                            p.description LIKE :searchPhrase')
                ->setParameter('searchPhrase', '%' . $searchPhrase . '%')

                // razeni
                ->orderBy('p.' . $sortData['attribute'], $sortData['order'])
            ;
        }
        //else

        return $queryBuilder->getQuery();
    }
}
