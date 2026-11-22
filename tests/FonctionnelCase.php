<?php

namespace App\Tests;

use App\Entity\Carburant\Plein;
use App\Entity\Carburant\Station;
use App\Entity\Carburant\Voiture;
use App\Entity\Security\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Base de tests fonctionnels : une base SQLite (var/data_test.db) recréée vide avant chaque test.
 */
abstract class FonctionnelCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $outil = new SchemaTool($this->em);
        $outil->dropDatabase();
        $outil->createSchema($this->em->getMetadataFactory()->getAllMetadata());
    }

    protected function creerUtilisateur(string $email = 'user@exemple.fr', array $roles = [], string $motDePasse = 'secret123'): Utilisateur
    {
        $utilisateur = (new Utilisateur())->setEmail($email)->setNom('Nom')->setPrenom('Prenom')->setRoles($roles);
        $utilisateur->setPassword(static::getContainer()->get('security.user_password_hasher')->hashPassword($utilisateur, $motDePasse));
        $this->em->persist($utilisateur);
        $this->em->flush();

        return $utilisateur;
    }

    /** @return array{Voiture, Station} */
    protected function creerVoitureEtStation(): array
    {
        $voiture = (new Voiture())->setMarque('Renault')->setModele('Clio')->setImmatriculation('AA-123-BB');
        $station = (new Station())->setNom('Leclerc')->setVille('Lyon');
        $this->em->persist($voiture);
        $this->em->persist($station);
        $this->em->flush();

        return [$voiture, $station];
    }

    protected function creerPlein(Voiture $voiture, Station $station): Plein
    {
        $plein = (new Plein())->setDate(new \DateTime('2026-01-15'))->setQuantite(40)->setPrix(70)->setKilometrage(12345)
            ->setVoiture($voiture)->setStation($station);
        $this->em->persist($plein);
        $this->em->flush();

        return $plein;
    }
}
