<?php

namespace App\Repository;

use App\Entity\User;
use App\Service\SortingService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Doctrine\ORM\Query;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    private SortingService $sorting;

    public function __construct(ManagerRegistry $registry, SortingService $sorting)
    {
        parent::__construct($registry, User::class);

        $this->sorting = $sorting;
    }

    public function createNew(): User
    {
        $user = new User();
        $user->setRegistered(new \DateTime('now'))
             ->setGender(User::GENDER_ID_UNDISCLOSED);

        return $user;
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User)
        {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    public function getQueryForSearchAndPagination($searchPhrase = null, string $sortAttribute = null): Query
    {
        $sortData = $this->sorting->createSortData($sortAttribute, User::getSortData());

        return $this->createQueryBuilder('u')

            //podminky
            ->orWhere('u.email LIKE :email')
            ->setParameter('email', '%' . $searchPhrase . '%')

            ->orWhere('CONCAT(u.nameFirst, \' \', u.nameLast) LIKE :fullName')
            ->setParameter('fullName', '%' . $searchPhrase . '%')

            ->orWhere('u.phoneNumber LIKE :phoneNumber')
            ->setParameter('phoneNumber', '%' . str_replace(' ', '', $searchPhrase) . '%')

            //razeni
            ->orderBy('u.' . $sortData['attribute'], $sortData['order'])
            ->getQuery();
    }
}
