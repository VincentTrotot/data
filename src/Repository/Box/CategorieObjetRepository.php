<?php

namespace App\Repository\Box;

use App\Entity\Box\CategorieObjet;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<CategorieObjet>
 *
 * @method CategorieObjet|null find($id, $lockMode = null, $lockVersion = null)
 * @method CategorieObjet|null findOneBy(array $criteria, array $orderBy = null)
 * @method CategorieObjet[]    findAll()
 * @method CategorieObjet[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategorieObjetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategorieObjet::class);
    }

    public function add(CategorieObjet $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CategorieObjet $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
