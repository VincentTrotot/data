<?php

namespace App\Repository\Box;

use App\Entity\Box\Carton;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Carton>
 *
 * @method Carton|null find($id, $lockMode = null, $lockVersion = null)
 * @method Carton|null findOneBy(array $criteria, array $orderBy = null)
 * @method Carton[]    findAll()
 * @method Carton[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CartonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Carton::class);
    }

    public function add(Carton $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Carton $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
