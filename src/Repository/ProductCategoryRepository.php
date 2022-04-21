<?php /** @noinspection SqlNoDataSourceInspection */

namespace App\Repository;

use App\Entity\ProductCategory;
use App\Entity\ProductSection;
use App\Service\CatalogProductFilterService;
use App\Service\CatalogCategoryFilterService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProductCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductCategory[]    findAll()
 * @method ProductCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductCategoryRepository extends ServiceEntityRepository
{
    private CatalogProductFilterService $productFilter;
    private CatalogCategoryFilterService $categoryFilter;

    public function __construct(ManagerRegistry $registry, CatalogProductFilterService $productFilter, CatalogCategoryFilterService $categoryFilter)
    {
        parent::__construct($registry, ProductCategory::class);

        $this->productFilter = $productFilter;
        $this->categoryFilter = $categoryFilter;
    }

    public function getNumberOfProductsForFilter(ProductCategory $category, ProductSection $section, string $searchPhrase = null, float $priceMin = null, float $priceMax = null, array $categoriesChosen = null)
    {
        $conn = $this->getEntityManager()->getConnection();

        $this->categoryFilter->createDataForCategoryProductCountQuery($category, $section, $searchPhrase, $priceMin, $priceMax, $categoriesChosen);
        $placeholderData = $this->categoryFilter->getProductCountPlaceholders();
        $clauseData = $this->categoryFilter->getProductCountClauses();

        $sql = sprintf('
            SELECT COUNT(*) FROM (
                SELECT joinTable.product_id
                FROM _product_category joinTable 
                JOIN product_category pc ON pc.id = joinTable.product_category_id
                WHERE joinTable.product_id IN (
                    SELECT id FROM product 
                    WHERE section_id = :section_id
                    %s
                    %s
                    %s
                    AND is_hidden = false
                    AND (NOT (available_since IS NOT NULL AND available_since > :now))
                    AND (NOT (hide_when_sold_out = true AND inventory <= 0))
                )
                GROUP BY joinTable.product_id
                %s
            ) tableResult;
        ', $clauseData['searchPhrase'], $clauseData['priceMin'], $clauseData['priceMax'], $clauseData['having']);

        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery($placeholderData);
        return $resultSet->fetchOne();
    }

    public function qbFindCategoriesInSection(?ProductSection $section): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('pc')
            ->select('pc', 'pcg', 'p')
            ->innerJoin('pc.productCategoryGroup', 'pcg')
            ->innerJoin('pc.products', 'p')
        ;

        return $this->productFilter
            ->initialize($queryBuilder, $section)
            ->addProductSearchConditions()
            ->getQueryBuilder()
        ;
    }

    public function qbFindAllAndFetchGroups(): QueryBuilder
    {
        return $this->createQueryBuilder('pc')
            ->select('pc', 'pcg')
            ->innerJoin('pc.productCategoryGroup', 'pcg')
        ;
    }
}