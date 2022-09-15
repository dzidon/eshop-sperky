<?php

namespace App\Repository;

use App\Entity\Detached\Search\Composition\PhraseSort;
use App\Entity\User;
use App\Pagination\Pagination;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
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
    private $request;

    public function __construct(ManagerRegistry $registry, RequestStack $requestStack)
    {
        parent::__construct($registry, User::class);

        $this->request = $requestStack->getCurrentRequest();
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

    public function getSearchPagination(PhraseSort $searchData): Pagination
    {
        $sortData = $searchData->getSort()->getDqlSortData();

        $query = $this->createQueryBuilder('u')

            //podminky
            ->orWhere('u.email LIKE :email')
            ->setParameter('email', '%' . $searchData->getPhrase()->getText() . '%')

            ->orWhere('CONCAT(u.nameFirst, \' \', u.nameLast) LIKE :fullName')
            ->setParameter('fullName', '%' . $searchData->getPhrase()->getText() . '%')

            ->orWhere('u.phoneNumber LIKE :phoneNumber')
            ->setParameter('phoneNumber', '%' . str_replace(' ', '', $searchData->getPhrase()->getText()) . '%')

            //razeni
            ->orderBy('u.' . $sortData['attribute'], $sortData['order'])
            ->getQuery()
            ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
        ;

        return new Pagination($query, $this->request);
    }
}
