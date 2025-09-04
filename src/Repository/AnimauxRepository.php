<?php

namespace App\Repository;

use App\Entity\Animaux;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Animaux>
 */
class AnimauxRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Animaux::class);
    }

    //    /**
    //     * @return Animaux[] Returns an array of Animaux objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Animaux
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
public function findByFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('a');

        if (!empty($filters['espece'])) {
            $qb->andWhere('a.espece = :espece')->setParameter('espece', $filters['espece']);
        }

        if (!empty($filters['race'])) {
            $qb->andWhere('a.race = :race')->setParameter('race', $filters['race']);
        }

        if (!empty($filters['age'])) {
            $qb->andWhere('a.age = :age')->setParameter('age', $filters['age']);
        }

        if (isset($filters['urgence']) && $filters['urgence'] !== '') {
            $qb->andWhere('a.urgence = :urgence')->setParameter('urgence', $filters['urgence']);
        }

        return $qb->getQuery()->getResult();
    }
}
