<?php

namespace App\Repository;

use App\Entity\Address;
use App\Entity\User;
use App\Service\SortingService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Address|null find($id, $lockMode = null, $lockVersion = null)
 * @method Address|null findOneBy(array $criteria, array $orderBy = null)
 * @method Address[]    findAll()
 * @method Address[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AddressRepository extends ServiceEntityRepository
{
    private SortingService $sorting;

    public function __construct(ManagerRegistry $registry, SortingService $sorting)
    {
        parent::__construct($registry, Address::class);

        $this->sorting = $sorting;
    }

    public function getQueryForPagination(User $user, $searchPhrase = null, string $sortAttribute = null): Query
    {
        $sortData = $this->sorting->createSortData($sortAttribute, Address::getSortData());

        return $this->createQueryBuilder('a')

            //jen pozadovany uzivatel
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)

            //vyhledavani
            ->andWhere('a.alias LIKE :searchPhrase OR
                        a.country LIKE :searchPhrase OR
                        a.street LIKE :searchPhrase OR
                        a.town LIKE :searchPhrase OR
                        a.zip LIKE :searchPhrase OR
                        a.company LIKE :searchPhrase OR
                        a.ic LIKE :searchPhrase OR
                        a.dic LIKE :searchPhrase OR
                        a.additionalInfo LIKE :searchPhrase')
            ->setParameter('searchPhrase', '%' . $searchPhrase . '%')

            //razeni
            ->orderBy('a.' . $sortData['attribute'], $sortData['order'])
            ->getQuery();
    }
}
