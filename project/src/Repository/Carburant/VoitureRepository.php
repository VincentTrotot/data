<?php

namespace App\Repository\Carburant;

use App\Entity\Carburant\Voiture;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Voiture>
 *
 * @method Voiture|null find($id, $lockMode = null, $lockVersion = null)
 * @method Voiture|null findOneBy(array $criteria, array $orderBy = null)
 * @method Voiture[]    findAll()
 * @method Voiture[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VoitureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Voiture::class);
    }

    public function add(Voiture $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Voiture $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneOrderedByPleinsDate(PleinRepository $pleinRepository, int $id): ?Voiture
    {
        $pleins = $pleinRepository->createQueryBuilder('p')
            ->where('p.voiture = :id')
            ->setParameter('id', $id)
            ->orderBy('p.date', 'DESC')
            ->getQuery()
            ->getResult();
        $pleins = new \Doctrine\Common\Collections\ArrayCollection($pleins);
        $voiture = $pleins[0]->getVoiture();

            if($voiture !== null) {
                $voiture->setPleins($pleins);
            }
            return $voiture;

    }

}
