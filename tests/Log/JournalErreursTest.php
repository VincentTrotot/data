<?php

namespace App\Tests\Log;

use App\Log\JournalErreurs;
use PHPUnit\Framework\TestCase;

class JournalErreursTest extends TestCase
{
    private string $fichier;

    protected function setUp(): void
    {
        $this->fichier = sys_get_temp_dir() . '/journal-' . bin2hex(random_bytes(4)) . '.jsonl';
    }

    protected function tearDown(): void
    {
        @unlink($this->fichier);
        @unlink($this->fichier . '.1');
    }

    /** @return array<string, mixed> */
    private function ligne(string $empreinte, string $date, string $url = '/a'): array
    {
        return [
            'date' => $date, 'niveau' => 'ERROR', 'canal' => 'request', 'message' => 'm ' . $date,
            'exception' => ['classe' => 'X', 'message' => 'm', 'fichier' => 'src/X.php', 'ligne' => 3, 'trace' => []],
            'url' => $url, 'methode' => 'GET', 'commande' => null, 'compte' => null, 'empreinte' => $empreinte,
        ];
    }

    /** @param list<array<string, mixed>> $lignes */
    private function ecrire(array $lignes, string $suffixe = ''): void
    {
        file_put_contents($this->fichier . $suffixe, implode("\n", array_map('json_encode', $lignes)) . "\n");
    }

    private function journal(): JournalErreurs
    {
        return new JournalErreurs($this->fichier);
    }

    public function testGroupesComptesEtTries(): void
    {
        $this->ecrire([
            $this->ligne('aaaaaaaaaaaa', '2026-10-01T10:00:00+02:00', '/vieux'),
            $this->ligne('bbbbbbbbbbbb', '2026-10-05T10:00:00+02:00'),
            $this->ligne('aaaaaaaaaaaa', '2026-10-09T10:00:00+02:00', '/recent'),
        ]);
        file_put_contents($this->fichier, "pas du json\n", FILE_APPEND);

        $groupes = $this->journal()->groupes();

        self::assertCount(2, $groupes);
        self::assertSame('aaaaaaaaaaaa', $groupes[0]['empreinte']);
        self::assertSame(2, $groupes[0]['nombre']);
        self::assertSame('/recent', $groupes[0]['url']);
        self::assertSame('src/X.php', $groupes[0]['fichier']);
        self::assertSame(3, $groupes[0]['ligne']);
        self::assertSame('2026-10-01', $groupes[0]['premiere']->format('Y-m-d'));
    }

    public function testOccurrencesDeLaPlusRecenteALaPlusAncienne(): void
    {
        $this->ecrire([
            $this->ligne('aaaaaaaaaaaa', '2026-10-01T10:00:00+02:00'),
            $this->ligne('aaaaaaaaaaaa', '2026-10-09T10:00:00+02:00'),
            $this->ligne('bbbbbbbbbbbb', '2026-10-05T10:00:00+02:00'),
        ]);
        $this->ecrire([$this->ligne('aaaaaaaaaaaa', '2026-10-03T10:00:00+02:00')], '.1');

        $occurrences = $this->journal()->occurrences('aaaaaaaaaaaa');

        self::assertSame(['2026-10-09', '2026-10-03', '2026-10-01'], array_map(fn (array $o): string => $o['_date']->format('Y-m-d'), $occurrences));
        self::assertCount(1, $this->journal()->occurrences('aaaaaaaaaaaa', 1));
    }

    public function testCompterDepuis(): void
    {
        $this->ecrire([
            $this->ligne('aaaaaaaaaaaa', '2026-10-01T10:00:00+02:00'),
            $this->ligne('aaaaaaaaaaaa', '2026-10-09T10:00:00+02:00'),
            $this->ligne('bbbbbbbbbbbb', '2026-10-05T10:00:00+02:00'),
        ]);

        self::assertSame(2, $this->journal()->compterDepuis(new \DateTimeImmutable('2026-10-04')));
        self::assertSame(1, $this->journal()->compterDepuis(new \DateTimeImmutable('2026-10-06')));
        self::assertSame(0, $this->journal()->compterDepuis(new \DateTimeImmutable('2026-11-01')));
    }

    public function testPurger(): void
    {
        $this->ecrire([
            $this->ligne('aaaaaaaaaaaa', '2026-09-01T10:00:00+02:00'),
            $this->ligne('bbbbbbbbbbbb', '2026-10-20T10:00:00+02:00'),
        ]);
        $this->ecrire([$this->ligne('cccccccccccc', '2026-08-01T10:00:00+02:00')], '.1');

        $retirees = $this->journal()->purger(new \DateTimeImmutable('2026-10-01'));

        self::assertSame(2, $retirees);
        self::assertFileDoesNotExist($this->fichier . '.1');
        self::assertSame(['bbbbbbbbbbbb'], array_column($this->journal()->groupes(), 'empreinte'));
    }

    public function testVider(): void
    {
        $this->ecrire([$this->ligne('aaaaaaaaaaaa', '2026-10-01T10:00:00+02:00')]);
        $this->ecrire([$this->ligne('aaaaaaaaaaaa', '2026-10-01T10:00:00+02:00')], '.1');

        $this->journal()->vider();

        self::assertSame([], $this->journal()->groupes());
        self::assertFileDoesNotExist($this->fichier);
        self::assertFileDoesNotExist($this->fichier . '.1');
    }
}
