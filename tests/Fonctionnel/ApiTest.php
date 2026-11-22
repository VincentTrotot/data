<?php

namespace App\Tests\Fonctionnel;

use App\Entity\Carburant\Plein;
use App\Entity\Carburant\Station;
use App\Entity\Carburant\Voiture;
use App\Tests\FonctionnelCase;

class ApiTest extends FonctionnelCase
{
    private const CLE = 'cle-de-test-0123456789';

    protected function setUp(): void
    {
        parent::setUp();
        // .env.test fixe API_KEY via phpunit.xml.dist (env) ; on vérifie ici qu'elle est bien la clé de test
        $this->assertSame(self::CLE, $_SERVER['API_KEY'] ?? $_ENV['API_KEY'] ?? null);
    }

    public function testAjoutDunPleinAvecLaBonneCle(): void
    {
        [$voiture, $station] = $this->creerVoitureEtStation();

        $this->client->request('GET', sprintf('/api/plein/add/40/70/12345/%d/%d/%s', $voiture->getId(), $station->getId(), self::CLE));

        $this->assertResponseRedirects('/');
        $this->assertSame(1, $this->em->getRepository(Plein::class)->count([]));
    }

    public function testPleinRefuseAvecUneMauvaiseCle(): void
    {
        [$voiture, $station] = $this->creerVoitureEtStation();

        $this->client->request('GET', sprintf('/api/plein/add/40/70/12345/%d/%d/mauvaise', $voiture->getId(), $station->getId()));

        $this->assertResponseStatusCodeSame(404);
        $this->assertSame(0, $this->em->getRepository(Plein::class)->count([]));
    }

    public function testPleinRefuseSansCle(): void
    {
        [$voiture, $station] = $this->creerVoitureEtStation();

        $this->client->request('GET', sprintf('/api/plein/add/40/70/12345/%d/%d/', $voiture->getId(), $station->getId()));

        $this->assertResponseStatusCodeSame(404);
        $this->assertSame(0, $this->em->getRepository(Plein::class)->count([]));
    }

    public function testPleinAvecVoitureInconnueRefuse(): void
    {
        [, $station] = $this->creerVoitureEtStation();

        $this->client->request('GET', sprintf('/api/plein/add/40/70/12345/999/%d/%s', $station->getId(), self::CLE));

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame(0, $this->em->getRepository(Plein::class)->count([]));
    }

    public function testAjoutVoitureEtStationProteges(): void
    {
        $this->client->request('GET', '/api/voiture/add/Renault/Clio/AA-123-BB/mauvaise');
        $this->assertResponseStatusCodeSame(404);
        $this->client->request('GET', '/api/station/add/Leclerc/Lyon/mauvaise');
        $this->assertResponseStatusCodeSame(404);
        $this->assertSame(0, $this->em->getRepository(Voiture::class)->count([]));
        $this->assertSame(0, $this->em->getRepository(Station::class)->count([]));

        $this->client->request('GET', '/api/voiture/add/Renault/Clio/AA-123-BB/'.self::CLE);
        $this->assertResponseIsSuccessful();
        $this->client->request('GET', '/api/station/add/Leclerc/Lyon/'.self::CLE);
        $this->assertResponseIsSuccessful();
        $this->assertSame(1, $this->em->getRepository(Voiture::class)->count([]));
        $this->assertSame(1, $this->em->getRepository(Station::class)->count([]));
    }

    public function testListesProtegees(): void
    {
        $this->creerVoitureEtStation();

        foreach (['/api/voitures', '/api/stations', '/api/voitures?cle=mauvaise', '/api/stations?cle='] as $url) {
            $this->client->request('GET', $url);
            $this->assertResponseStatusCodeSame(404, $url);
        }

        $this->client->request('GET', '/api/voitures?cle='.self::CLE);
        $this->assertResponseIsSuccessful();
        $this->assertSame(['Renault Clio' => 1], json_decode($this->client->getResponse()->getContent(), true));
    }
}
