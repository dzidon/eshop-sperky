<?php

namespace App\Repository;

use App\CatalogFilter\CatalogProductCategoryCountNativeQueryDataBuilder;
use App\CatalogFilter\CatalogProductSearchNativeQueryDataBuilder;
use App\Entity\ProductCategoryGroup;
use App\Entity\ProductSection;
use App\Entity\ProductCategory;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method ProductCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductCategory[]    findAll()
 * @method ProductCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductCategory::class);
    }

    public function getNumberOfProductsForFilter(ProductCategory $category, ProductSection $section, string $searchPhrase = null, float $priceMin = null, float $priceMax = null, array $categoriesChosen = null)
    {
        $conn = $this->getEntityManager()->getConnection();

        $productSearchData = (new CatalogProductSearchNativeQueryDataBuilder()) // WHERE
            ->withSection($section)
            ->withSearchPhrase($searchPhrase)
            ->withPriceMin($priceMin)
            ->withPriceMax($priceMax)
            ->withWhere()
            ->build()
        ;

        $categoryCountData = (new CatalogProductCategoryCountNativeQueryDataBuilder($category)) // HAVING
            ->withCategoriesChosen($categoriesChosen)
            ->withPrefix('pc')
            ->withHaving()
            ->build()
        ;

        $sql = sprintf('
            SELECT COUNT(*) FROM (
                SELECT joinTable.product_id
                FROM _product_category joinTable 
                JOIN product_category pc ON pc.id = joinTable.product_category_id
                WHERE joinTable.product_id IN (
                    SELECT id FROM product 
                    %s
                )
                GROUP BY joinTable.product_id
                %s
            ) tableResult;
        ', $productSearchData->getClause(), $categoryCountData->getClause());

        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery(array_merge($productSearchData->getPlaceholders(), $categoryCountData->getPlaceholders()));

        return $resultSet->fetchOne();
    }

    public function findProductCategoriesInSection(?ProductSection $section): array
    {
        $productSearchData = (new CatalogProductSearchNativeQueryDataBuilder())
            ->withSection($section)
            ->withPrefix('p')
            ->withWhere()
            ->build()
        ;

        $sql = 'SELECT DISTINCT pc.id AS `pc.id`, pc.name AS `pc.name`, pc.created AS `pc.created`, pc.updated AS `pc.updated`,
                pcg.id AS `pcg.id`, pcg.name AS `pcg.name`, pcg.created AS `pcg.created`, pcg.updated AS `pcg.updated`
                FROM product_category AS pc
                JOIN _product_category AS _pc ON (_pc.product_category_id = pc.id)
                JOIN product_category_group AS pcg ON (pc.product_category_group_id = pcg.id)
                JOIN product AS p ON (_pc.product_id = p.id)
                JOIN product_section AS ps ON (p.section_id = ps.id)
                ' . $productSearchData->getClause();

        $rsm = new ResultSetMappingBuilder($this->_em);
        $rsm->addRootEntityFromClassMetadata(ProductCategory::class, 'pc', [
            'id' => 'pc.id',
            'name' => 'pc.name',
            'created' => 'pc.created',
            'updated' => 'pc.updated',
        ]);

        $rsm->addJoinedEntityFromClassMetadata(ProductCategoryGroup::class, 'pcg', 'pc', 'productCategoryGroup', [
            'id' => 'pcg.id',
            'name' => 'pcg.name',
            'created' => 'pcg.created',
            'updated' => 'pcg.updated',
        ]);

        $query = $this->_em->createNativeQuery($sql, $rsm);
        $query->setParameters($productSearchData->getPlaceholders());

        $categories = $query->getResult();
        $categoriesGrouped = [];

        /** @var ProductCategory $category */
        foreach ($categories as $category)
        {
            $categoryGroup = $category->getProductCategoryGroup();
            $categoriesGrouped[$categoryGroup->getName()][$category->getName()] = $category;
        }

        return $categoriesGrouped;
    }
}