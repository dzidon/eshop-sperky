<?php

namespace App\Repository;

use App\Entity\TextContent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TextContent>
 *
 * @method TextContent|null find($id, $lockMode = null, $lockVersion = null)
 * @method TextContent|null findOneBy(array $criteria, array $orderBy = null)
 * @method TextContent[]    findAll()
 * @method TextContent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TextContentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TextContent::class);
    }

    public function findByNames(array $names)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.name IN (:names)')
            ->setParameter('names', $names)
            ->getQuery()
            ->getResult()
        ;
    }
}
