<?php

namespace App\Repository;

use App\Entity\Address;
use App\Entity\User;
use App\Pagination\Pagination;
use App\Service\SortingService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @method Address|null find($id, $lockMode = null, $lockVersion = null)
 * @method Address|null findOneBy(array $criteria, array $orderBy = null)
 * @method Address[]    findAll()
 * @method Address[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AddressRepository extends ServiceEntityRepository
{
    private SortingService $sorting;
    private $request;

    public function __construct(ManagerRegistry $registry, SortingService $sorting, RequestStack $requestStack)
    {
        parent::__construct($registry, Address::class);

        $this->sorting = $sorting;
        $this->request = $requestStack->getCurrentRequest();
    }

    public function getSearchPagination(User $user, $searchPhrase = null, string $sortAttribute = null): Pagination
    {
        $sortData = $this->sorting->createSortData($sortAttribute, Address::getSortData());

        $query = $this->createQueryBuilder('a')

            //jen pozadovany uzivatel
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)

            //vyhledavani
            ->andWhere('a.alias LIKE :searchPhrase')
            ->setParameter('searchPhrase', '%' . $searchPhrase . '%')

            //razeni
            ->orderBy('a.' . $sortData['attribute'], $sortData['order'])
            ->getQuery()
        ;

        return new Pagination($query, $this->request);
    }
}
