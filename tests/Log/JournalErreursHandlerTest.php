<?php

namespace App\Tests\Log;

use App\Log\JournalErreursHandler;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class JournalErreursHandlerTest extends TestCase
{
    private string $dossier;
    private string $fichier;

    protected function setUp(): void
    {
        $this->dossier = sys_get_temp_dir() . '/erreurs-' . bin2hex(random_bytes(4));
        $this->fichier = $this->dossier . '/erreurs.jsonl';
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dossier . '/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($this->dossier);
    }

    private function handler(?Request $requete = null, ?string $fichier = null): JournalErreursHandler
    {
        $pile = new RequestStack();
        if ($requete) {
            $pile->push($requete);
        }

        return new JournalErreursHandler($fichier ?? $this->fichier, \dirname(__DIR__, 2), $pile, $this->createStub(Security::class));
    }

    /** @param array<string, mixed> $contexte */
    private function enregistrement(string $message, array $contexte = []): LogRecord
    {
        return new LogRecord(new \DateTimeImmutable(), 'request', Level::Error, $message, $contexte);
    }

    /** @return list<array<string, mixed>> */
    private function lignes(): array
    {
        $lignes = [];
        foreach (file($this->fichier, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $brut) {
            $ligne = json_decode($brut, true);
            self::assertIsArray($ligne);
            $lignes[] = $ligne;
        }

        return $lignes;
    }

    private function profonde(int $reste): void
    {
        if ($reste === 0) {
            throw new \RuntimeException('Boum 42', 0, new \LogicException('avant'));
        }
        $this->profonde($reste - 1);
    }

    private function exceptionProfonde(): \Throwable
    {
        try {
            $this->profonde(30);
        } catch (\RuntimeException $e) {
            return $e;
        }
        throw new \LogicException('jamais atteint');
    }

    public function testUneErreurAvecExceptionEstEcrite(): void
    {
        $this->handler(Request::create('/admin/dossier?token=secret', 'POST'))
            ->handle($this->enregistrement('Plantage', ['exception' => $this->exceptionProfonde()]));

        $lignes = $this->lignes();
        self::assertCount(1, $lignes);
        $ligne = $lignes[0];
        self::assertSame('ERROR', $ligne['niveau']);
        self::assertSame('request', $ligne['canal']);
        self::assertSame('/admin/dossier', $ligne['url']);
        self::assertSame('POST', $ligne['methode']);
        self::assertNull($ligne['compte']);
        $exception = $ligne['exception'];
        self::assertIsArray($exception);
        self::assertSame('tests/Log/JournalErreursHandlerTest.php', $exception['fichier']);
        self::assertIsArray($exception['trace']);
        self::assertCount(15, $exception['trace']);
        self::assertIsString($exception['trace'][0]);
        self::assertStringStartsWith('tests/Log/JournalErreursHandlerTest.php:', $exception['trace'][0]);
        self::assertSame(['classe' => \LogicException::class, 'message' => 'avant'], $exception['precedente']);
        self::assertIsString($ligne['empreinte']);
        self::assertMatchesRegularExpression('/^[0-9a-f]{12}$/', $ligne['empreinte']);
        self::assertStringNotContainsString('secret', (string) file_get_contents($this->fichier));
    }

    public function testLes4xxSontIgnoreesEtLes5xxGardees(): void
    {
        $handler = $this->handler();
        $handler->handle($this->enregistrement('404', ['exception' => new NotFoundHttpException()]));
        self::assertFileDoesNotExist($this->fichier);

        $handler->handle($this->enregistrement('503', ['exception' => new HttpException(503)]));
        self::assertCount(1, $this->lignes());
    }

    public function testLEmpreinteIgnoreLesNombres(): void
    {
        $handler = $this->handler();
        $handler->handle($this->enregistrement('Dossier 12 introuvable'));
        $handler->handle($this->enregistrement('Dossier 345 introuvable'));
        $handler->handle($this->enregistrement('Autre chose'));

        $lignes = $this->lignes();
        self::assertSame($lignes[0]['empreinte'], $lignes[1]['empreinte']);
        self::assertNotSame($lignes[0]['empreinte'], $lignes[2]['empreinte']);
        self::assertNull($lignes[0]['url']);
    }

    public function testRotationA5Mo(): void
    {
        mkdir($this->dossier, 0700, true);
        file_put_contents($this->fichier, str_repeat('x', JournalErreursHandler::TAILLE_MAXIMALE + 1));

        $this->handler()->handle($this->enregistrement('Neuf'));

        self::assertFileExists($this->fichier . '.1');
        self::assertCount(1, $this->lignes());
    }

    public function testUnDossierNonInscriptibleNeLevePasDException(): void
    {
        $handler = $this->handler(null, '/proc/inexistant/erreurs.jsonl');

        self::assertTrue($handler->handle($this->enregistrement('Perdu')) === false);
    }
}
