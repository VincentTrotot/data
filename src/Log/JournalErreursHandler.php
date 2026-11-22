<?php

namespace App\Log;

use App\Entity\Security\Utilisateur;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Garde les erreurs de l'appli (niveau error et plus) dans un fichier JSON Lines que l'admin consulte (#65).
 * Ne lève jamais d'exception : une erreur d'écriture est avalée.
 */
class JournalErreursHandler extends AbstractProcessingHandler
{
    public const TAILLE_MAXIMALE = 5 * 1024 * 1024;
    private const CADRES = 15;

    public function __construct(
        #[Autowire('%app.fichier_erreurs%')]
        private string $fichier,
        #[Autowire('%kernel.project_dir%')]
        private string $racine,
        private RequestStack $requestStack,
        private Security $security,
    ) {
        parent::__construct(Level::Error, true);
    }

    protected function write(LogRecord $record): void
    {
        try {
            $exception = $record->context['exception'] ?? null;
            if ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() < 500) {
                return;
            }
            $ligne = json_encode(
                $this->ligne($record, $exception instanceof \Throwable ? $exception : null),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR
            );

            $dossier = \dirname($this->fichier);
            if (!is_dir($dossier)) {
                @mkdir($dossier, 0700, true);
            }
            clearstatcache(true, $this->fichier);
            if (is_file($this->fichier) && filesize($this->fichier) > self::TAILLE_MAXIMALE) {
                @rename($this->fichier, $this->fichier . '.1');
            }
            $neuf = !is_file($this->fichier);
            @file_put_contents($this->fichier, $ligne . "\n", FILE_APPEND | LOCK_EX);
            if ($neuf) {
                @chmod($this->fichier, 0600);
            }
        } catch (\Throwable) {
            // le journal des erreurs ne doit jamais en provoquer une
        }
    }

    /** @return array<string, mixed> */
    private function ligne(LogRecord $record, ?\Throwable $exception): array
    {
        $requete = $this->requestStack->getMainRequest();
        $utilisateur = $this->security->getUser();
        $message = $this->couper($record->message);

        /** @var array{classe: string, message: string, fichier: string, ligne: int, trace: list<string>, precedente?: array{classe: string, message: string}}|null $detail */
        $detail = null;
        if ($exception !== null) {
            $detail = [
                'classe' => $exception::class,
                'message' => $this->couper($exception->getMessage()),
                'fichier' => $this->relatif($exception->getFile()),
                'ligne' => $exception->getLine(),
                'trace' => $this->trace($exception),
            ];
            if ($exception->getPrevious() !== null) {
                $detail['precedente'] = [
                    'classe' => $exception->getPrevious()::class,
                    'message' => $this->couper($exception->getPrevious()->getMessage()),
                ];
            }
        }

        $commande = null;
        if ($requete === null && PHP_SAPI === 'cli') {
            $argv = $_SERVER['argv'] ?? [];
            $argument = \is_array($argv) ? ($argv[1] ?? null) : null;
            $commande = \is_string($argument) && preg_match('/^[a-z][\w:-]*$/i', $argument) === 1 ? $argument : null;
        }

        return [
            'date' => $record->datetime->setTimezone(new \DateTimeZone('Europe/Paris'))->format(\DATE_ATOM),
            'niveau' => strtoupper($record->level->getName()),
            'canal' => $record->channel,
            'message' => $message,
            'exception' => $detail,
            'url' => $requete?->getPathInfo(),
            'methode' => $requete?->getMethod(),
            'commande' => $commande,
            'compte' => $utilisateur instanceof Utilisateur ? $utilisateur->getId() : null,
            'empreinte' => $this->empreinte($record, $detail),
        ];
    }

    /** @param array{classe: string, message: string, fichier: string, ligne: int, trace: list<string>}|null $detail */
    private function empreinte(LogRecord $record, ?array $detail): string
    {
        $normalise = static fn (string $texte): string => (string) preg_replace('/\d+/', '#', $texte);
        $source = $detail !== null
            ? \sprintf('%s|%s|%d|%s', $detail['classe'], $detail['fichier'], $detail['ligne'], $normalise($detail['message']))
            : \sprintf('%s|%s', $record->channel, $normalise($record->message));

        return substr(sha1($source), 0, 12);
    }

    /** @return list<string> */
    private function trace(\Throwable $exception): array
    {
        $cadres = [];
        foreach (\array_slice($exception->getTrace(), 0, self::CADRES) as $cadre) {
            $appel = ($cadre['class'] ?? '') . ($cadre['type'] ?? '') . $cadre['function'] . '()';
            $cadres[] = isset($cadre['file'])
                ? \sprintf('%s:%d %s', $this->relatif($cadre['file']), $cadre['line'] ?? 0, $appel)
                : $appel;
        }

        return $cadres;
    }

    private function relatif(string $chemin): string
    {
        $racine = rtrim($this->racine, '/') . '/';

        return str_starts_with($chemin, $racine) ? substr($chemin, \strlen($racine)) : $chemin;
    }

    private function couper(string $texte): string
    {
        return mb_substr($texte, 0, 500);
    }
}
