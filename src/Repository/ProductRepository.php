<?php

namespace App\Repository;

use App\Entity\Product;
use App\Service\SortingService;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

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

    public function findOneAndFetchEverything(array $criteria)
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p, ps, pc, pcg, po, pi, pig, pimg')
            ->leftJoin('p.section', 'ps')
            ->leftJoin('p.categories', 'pc')
            ->leftJoin('pc.productCategoryGroup', 'pcg')
            ->leftJoin('p.options', 'po')
            ->leftJoin('p.info', 'pi')
            ->leftJoin('pi.productInformationGroup', 'pig')
            ->leftJoin('p.images', 'pimg')
            ->orderBy('pimg.priority', 'DESC');

        foreach ($criteria as $name => $value)
        {
            $qb->andWhere(sprintf('p.%s = :%s', $name, $name))->setParameter($name, $value);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findRelated(Product $product, int $quantity)
    {
        $productsCount = $this->getQueryForRelated($product, $quantity)
            ->select('count(p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $maxOffset = $productsCount-$quantity;
        if($maxOffset < 0)
        {
            $maxOffset = 0;
        }

        try
        {
            $randomOffset = random_int(0, $maxOffset);
        }
        catch (Exception $e)
        {
            $randomOffset = 0;
        }

        $products = $this->getQueryForRelated($product, $quantity)
            ->setFirstResult($randomOffset)
            ->getQuery()
            ->getResult();

        shuffle($products);
        return $products;
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

    private function getQueryForRelated(Product $product, int $quantity): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('p');
        $this->addVisibilityCondition($queryBuilder);

        return $queryBuilder
            ->andWhere('p.id != :viewedProductId')
            ->andWhere('p.section = :viewedProductSection')
            ->andWhere('p.section = :viewedProductSection')
            ->setParameter('viewedProductId', $product->getId())
            ->setParameter('viewedProductSection', $product->getSection())
            ->orderBy('p.created', 'DESC')
            ->setMaxResults($quantity);
    }

    private function addVisibilityCondition(QueryBuilder $queryBuilder): void
    {
        $queryBuilder
            ->andWhere('p.isHidden = false')
            ->andWhere('NOT (p.availableSince IS NOT NULL AND p.availableSince > :now)')
            ->andWhere('NOT (p.hideWhenSoldOut = true AND p.inventory <= 0)')
            ->setParameter('now', new DateTime('now'));
    }
}
