<?php

namespace App\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Pour les tests qui utilisent la base de test (var/data_test.db) sans passer par FonctionnelCase */
trait BaseDeDonnees
{
    /** Recrée le schéma vide (à appeler avant de démarrer le noyau du test) */
    protected static function reinitialiserBase(): void
    {
        KernelTestCase::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        \assert($em instanceof EntityManagerInterface);
        $outil = new SchemaTool($em);
        $outil->dropDatabase();
        $outil->createSchema($em->getMetadataFactory()->getAllMetadata());
        KernelTestCase::ensureKernelShutdown();
    }

    /**
     * @template T of object
     * @param class-string<T> $classe
     * @return T
     */
    protected static function service(string $classe): object
    {
        $service = static::getContainer()->get($classe);
        \assert($service instanceof $classe);

        return $service;
    }

    /** Le fichier des erreurs de l'appli de l'environnement de test */
    protected static function fichierErreurs(): string
    {
        $fichier = static::getContainer()->getParameter('app.fichier_erreurs');
        \assert(\is_string($fichier));

        return $fichier;
    }
}
