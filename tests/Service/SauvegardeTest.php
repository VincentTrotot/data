<?php

namespace App\Tests\Service;

use App\Service\Sauvegarde;
use App\Tests\BaseDeDonnees;
use Doctrine\DBAL\Connection;
use Symfony\Component\Clock\MockClock;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SauvegardeTest extends KernelTestCase
{
    use BaseDeDonnees;

    private string $dossier;

    protected function setUp(): void
    {
        self::reinitialiserBase();
        self::bootKernel();
        $this->dossier = \sys_get_temp_dir() . '/data-sauvegardes-' . \bin2hex(\random_bytes(4));
    }

    protected function tearDown(): void
    {
        foreach (\glob($this->dossier . '/*') ?: [] as $fichier) {
            \unlink($fichier);
        }
        if (\is_dir($this->dossier)) {
            \rmdir($this->dossier);
        }
        parent::tearDown();
    }

    public function testUneCopieCompleteCompresseeEtDatee(): void
    {
        $fichier = $this->sauvegarde('2026-10-24 03:00')->sauvegarder();

        self::assertSame($this->dossier . '/data-2026-10-24.db.gz', $fichier);
        self::assertSame(0o600, \fileperms($fichier) & 0o777);
        $copie = $this->dossier . '/verif.db';
        \file_put_contents($copie, \gzdecode((string) \file_get_contents($fichier)));
        $base = new \PDO('sqlite:' . $copie, options: [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        $lire = fn (string $sql) => ($base->query($sql) ?: throw new \RuntimeException($sql))->fetchColumn();
        self::assertSame('ok', $lire('PRAGMA integrity_check'));
        $personnes = self::service(Connection::class)->fetchOne('SELECT COUNT(*) FROM plein');
        self::assertEquals($personnes, $lire('SELECT COUNT(*) FROM plein'));
        unset($base, $lire);
        \unlink($copie);

        // relancée le même jour : rien de plus
        $this->sauvegarde('2026-10-24 22:00')->sauvegarder();
        self::assertSame(['data-2026-10-24.db.gz'], \array_map('basename', \glob($this->dossier . '/*') ?: []));
    }

    public function testLaCopieDeDeploiementPorteUnSuffixeEtResteHorsRotation(): void
    {
        $sauvegarde = $this->sauvegarde('2026-10-24 15:30');
        $fichier = $sauvegarde->sauvegarder('deploiement');

        self::assertSame($this->dossier . '/data-2026-10-24-1530-deploiement.db.gz', $fichier);
        self::assertStringContainsString('SQLite', \substr((string) \gzdecode((string) \file_get_contents($fichier)), 0, 16));

        // la rotation quotidienne et l'état ne la voient pas
        \touch($this->dossier . '/data-2025-01-05-0900-deploiement.db.gz');
        self::assertSame([], $sauvegarde->nettoyer());
        self::assertSame([], $sauvegarde->etat(new \DateTimeImmutable('2026-10-24 16:00'))['copies']);
    }

    public function testUneRaisonInconnueEstRefusee(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->sauvegarde('2026-10-24 15:30')->sauvegarder('autre');
    }

    public function testLesCopiesDeDeploiementSontPurgeesApresTrenteJours(): void
    {
        \mkdir($this->dossier);
        foreach (['2026-09-20-1000', '2026-09-25-1000', '2026-10-20-1000'] as $horodatage) {
            \touch(\sprintf('%s/data-%s-deploiement.db.gz', $this->dossier, $horodatage));
        }
        \touch($this->dossier . '/data-2026-09-01.db.gz');

        $supprimes = $this->sauvegarde('2026-10-24 03:00')->purgerDeploiements();

        self::assertSame(['data-2026-09-20-1000-deploiement.db.gz'], $supprimes);
        self::assertFileExists($this->dossier . '/data-2026-09-25-1000-deploiement.db.gz');
        self::assertFileExists($this->dossier . '/data-2026-09-01.db.gz');
    }

    public function testOnGardeQuatorzeJoursEtUneCopieParMoisSurUnAn(): void
    {
        \mkdir($this->dossier);
        $jour = new \DateTimeImmutable('2025-08-01');
        while ($jour <= new \DateTimeImmutable('2026-10-24')) {
            \touch(\sprintf('%s/data-%s.db.gz', $this->dossier, $jour->format('Y-m-d')));
            $jour = $jour->modify('+1 day');
        }
        // un autre fichier n'est pas touché
        \touch($this->dossier . '/autre.txt');

        $this->sauvegarde('2026-10-24 03:00')->nettoyer();

        $restants = \array_map('basename', \glob($this->dossier . '/data-*') ?: []);
        $attendus = [];
        $mois = new \DateTimeImmutable('2025-11-01');
        for ($i = 0; $i < 12; $i++) {
            $attendus[] = 'data-' . $mois->modify("+$i months")->format('Y-m') . '-01.db.gz';
        }
        for ($j = 11; $j <= 24; $j++) {
            $attendus[] = "data-2026-10-$j.db.gz";
        }
        self::assertSame($attendus, $restants);
        self::assertFileExists($this->dossier . '/autre.txt');
    }

    public function testAucuneCopieDonneLAlerteUn(): void
    {
        $etat = $this->sauvegarde('2026-11-01 10:00')->etat(new \DateTimeImmutable('2026-11-01 10:00'));

        self::assertSame([], $etat['copies']);
        self::assertNull($etat['derniere']);
        self::assertSame('Aucune sauvegarde sur le serveur : la tâche planifiée est-elle en place ?', $etat['alerte']);
    }

    public function testUneCopieDHierNeDonnePasDAlerte(): void
    {
        \mkdir($this->dossier);
        \file_put_contents($this->dossier . '/data-2026-10-31.db.gz', 'xx');

        $etat = $this->sauvegarde('2026-11-01 10:00')->etat(new \DateTimeImmutable('2026-11-01 10:00'));

        self::assertNull($etat['alerte']);
        self::assertSame(2, $etat['taille']);
        self::assertSame('2026-10-31', $etat['derniere']?->format('Y-m-d'));
    }

    public function testUneCopieDeTroisJoursDonneLAlerteDeux(): void
    {
        \mkdir($this->dossier);
        \touch($this->dossier . '/data-2026-10-29.db.gz');
        \touch($this->dossier . '/data-2026-10-25.db.gz');
        \touch($this->dossier . '/data-2026-10-27.db.gz');

        $etat = $this->sauvegarde('2026-11-01 10:00')->etat(new \DateTimeImmutable('2026-11-01 10:00'));

        self::assertSame(
            'Pas de sauvegarde depuis le jeudi 29 octobre 2026 : vérifie la tâche planifiée.',
            $etat['alerte']
        );
        self::assertSame(
            ['data-2026-10-29.db.gz', 'data-2026-10-27.db.gz', 'data-2026-10-25.db.gz'],
            \array_column($etat['copies'], 'nom')
        );
    }

    public function testLEchecDeLaPurgeDonneLAlerteTrois(): void
    {
        \mkdir($this->dossier);
        \touch($this->dossier . '/data-2026-11-01.db.gz');
        $sauvegarde = $this->sauvegarde('2026-11-01 10:00');
        $sauvegarde->ecrireCompteRendu([
            'debut' => '2026-11-01T03:00:02+01:00',
            'fin' => '2026-11-01T03:00:09+01:00',
            'sauvegarde' => ['code' => 0, 'erreur' => null],
            'purge' => ['code' => 1, 'erreur' => 'RuntimeException: base verrouillée'],
        ]);

        self::assertSame(
            'La dernière tâche de nuit a échoué (purge : RuntimeException: base verrouillée).',
            $sauvegarde->etat(new \DateTimeImmutable('2026-11-01 10:00'))['alerte']
        );
    }

    public function testLeCompteRenduSeRelitEtUnJsonIllisibleDonneNull(): void
    {
        $sauvegarde = $this->sauvegarde('2026-11-01 10:00');
        self::assertNull($sauvegarde->compteRendu());

        $compteRendu = ['debut' => '2026-11-01T03:00:02+01:00', 'sauvegarde' => ['code' => 0, 'erreur' => null]];
        $sauvegarde->ecrireCompteRendu($compteRendu);
        self::assertSame($compteRendu, $sauvegarde->compteRendu());
        self::assertSame(0o600, \fileperms($this->dossier . '/derniere-execution.json') & 0o777);

        \file_put_contents($this->dossier . '/derniere-execution.json', '{pas du json');
        self::assertNull($sauvegarde->compteRendu());
    }

    private function sauvegarde(string $maintenant): Sauvegarde
    {
        return new Sauvegarde(self::service(Connection::class), new MockClock($maintenant), $this->dossier);
    }
}
