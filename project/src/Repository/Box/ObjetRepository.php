<?php

namespace App\Repository\Box;

use App\Entity\Box\Objet;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Objet>
 *
 * @method Objet|null find($id, $lockMode = null, $lockVersion = null)
 * @method Objet|null findOneBy(array $criteria, array $orderBy = null)
 * @method Objet[]    findAll()
 * @method Objet[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ObjetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Objet::class);
    }

    public function add(Objet $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Objet $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAll()
    {
        $data= $this->createQueryBuilder('o')
            ->select('o, car, cat, m')
            ->leftJoin('o.carton', 'car')
            ->leftJoin('o.categorie', 'cat')
            ->leftJoin('o.mouvements', 'm')
            ->orderBy('o.carton', 'ASC')
            ->addOrderBy('car.numero', 'ASC')
            ->addOrderBy('cat.nom', 'ASC')
            ->addOrderBy('o.nom', 'ASC')
            ->getQuery()
            ->getResult();

            
            usort($data, function($a, $b) {
                if($a->isIn() && !$b->isIn()) {
                    return 1;
                } elseif(!$a->isIn() && $b->isIn()) {
                    return -1;
                } else {
                    return 0;
                }
            });

            usort($data, function($a, $b) {
                if($a->getCarton() == null && $b->getCarton() != null) {
                    return -1;
                } elseif($a->getCarton() != null && $b->getCarton() == null) {
                    return 1;
                } elseif($a->getCarton() == null && $b->getCarton() == null) {
                    return 0;
                } else {
                    return ($a->getCarton()->getNumero() < $b->getCarton()->getNumero()) ? -1 : 1;
                }
            });
            
        return $data;
    }

}
