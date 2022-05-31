<?php

namespace App\Repository\Carburant;

use App\Entity\Carburant\Plein;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Plein>
 *
 * @method Plein|null find($id, $lockMode = null, $lockVersion = null)
 * @method Plein|null findOneBy(array $criteria, array $orderBy = null)
 * @method Plein[]    findAll()
 * @method Plein[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PleinRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Plein::class);
    }

    public function add(Plein $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Plein $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAll()
    {
        return $this->findBy(array(), array('date' => 'DESC'));
    }

    public function findAllByDate($order)
    {
        return $this->findBy(array(), array('date' => $order));
    }

//    /**
//     * @return Plein[] Returns an array of Plein objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Plein
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
