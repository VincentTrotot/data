<?php

namespace App\Log;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Lecture du fichier d'erreurs écrit par `JournalErreursHandler` et de sa copie `.1` (#65)
 *
 * @phpstan-type Ligne array{empreinte: string, date: string, _date: \DateTimeImmutable, niveau?: string,
 *     message?: string, url?: string|null, methode?: string|null, commande?: string|null, compte?: int|null,
 *     exception?: array{classe?: string, message?: string, fichier?: string, ligne?: int, trace?: list<string>,
 *     precedente?: array{classe: string, message: string}}|null}
 */
class JournalErreurs
{
    public function __construct(
        #[Autowire('%app.fichier_erreurs%')]
        private string $fichier,
    ) {
    }

    /**
     * @return list<array{empreinte: string, niveau: string, message: string, classe: ?string, fichier: ?string,
     *     ligne: ?int, nombre: int, premiere: \DateTimeImmutable, derniere: \DateTimeImmutable, url: ?string}>
     */
    public function groupes(): array
    {
        /** @var array<string, array{nombre: int, premiere: \DateTimeImmutable, derniere: \DateTimeImmutable, recente: Ligne}> $groupes */
        $groupes = [];
        foreach ($this->lignes() as $ligne) {
            $date = $ligne['_date'];
            $cle = $ligne['empreinte'];
            if (!isset($groupes[$cle])) {
                $groupes[$cle] = ['nombre' => 0, 'premiere' => $date, 'derniere' => $date, 'recente' => $ligne];
            }
            ++$groupes[$cle]['nombre'];
            if ($date < $groupes[$cle]['premiere']) {
                $groupes[$cle]['premiere'] = $date;
            }
            if ($date >= $groupes[$cle]['derniere']) {
                $groupes[$cle]['derniere'] = $date;
                $groupes[$cle]['recente'] = $ligne;
            }
        }

        $resultat = [];
        foreach ($groupes as $empreinte => $groupe) {
            $ligne = $groupe['recente'];
            $exception = $ligne['exception'] ?? null;
            $resultat[] = [
                'empreinte' => $empreinte,
                'niveau' => $ligne['niveau'] ?? '',
                'message' => $ligne['message'] ?? '',
                'classe' => $exception['classe'] ?? null,
                'fichier' => $exception['fichier'] ?? null,
                'ligne' => $exception['ligne'] ?? null,
                'nombre' => $groupe['nombre'],
                'premiere' => $groupe['premiere'],
                'derniere' => $groupe['derniere'],
                'url' => $ligne['url'] ?? null,
            ];
        }
        usort($resultat, static fn (array $a, array $b): int => $b['derniere'] <=> $a['derniere']);

        return $resultat;
    }

    /**
     * Les dernières lignes d'une empreinte, de la plus récente à la plus ancienne
     *
     * @return list<Ligne>
     */
    public function occurrences(string $empreinte, int $limite = 20): array
    {
        $lignes = array_filter($this->lignes(), static fn (array $ligne): bool => $ligne['empreinte'] === $empreinte);
        usort($lignes, static fn (array $a, array $b): int => $b['_date'] <=> $a['_date']);

        return \array_slice($lignes, 0, $limite);
    }

    /** Nombre d'empreintes distinctes vues depuis cette date */
    public function compterDepuis(\DateTimeImmutable $depuis): int
    {
        $vues = [];
        foreach ($this->lignes() as $ligne) {
            if ($ligne['_date'] >= $depuis) {
                $vues[$ligne['empreinte']] = true;
            }
        }

        return \count($vues);
    }

    public function vider(): void
    {
        foreach ([$this->fichier, $this->fichier . '.1'] as $chemin) {
            if (is_file($chemin)) {
                @unlink($chemin);
            }
        }
    }

    /** Retire les lignes plus anciennes que la date ; renvoie leur nombre */
    public function purger(\DateTimeImmutable $avant): int
    {
        $retirees = 0;
        foreach ([$this->fichier . '.1', $this->fichier] as $chemin) {
            if (!is_file($chemin)) {
                continue;
            }
            $gardees = [];
            foreach ($this->lire($chemin) as [$brut, $ligne]) {
                if ($ligne !== null && $ligne['_date'] < $avant) {
                    ++$retirees;
                } elseif ($ligne !== null) {
                    $gardees[] = $brut;
                }
            }
            if ($gardees === []) {
                @unlink($chemin);
            } elseif (\count($gardees) !== \count(file($chemin, FILE_SKIP_EMPTY_LINES) ?: [])) {
                $temporaire = $chemin . '.tmp';
                file_put_contents($temporaire, implode("\n", $gardees) . "\n", LOCK_EX);
                @chmod($temporaire, 0600);
                rename($temporaire, $chemin);
            }
        }

        return $retirees;
    }

    /** @return list<Ligne> */
    private function lignes(): array
    {
        $lignes = [];
        foreach ([$this->fichier . '.1', $this->fichier] as $chemin) {
            foreach ($this->lire($chemin) as [, $ligne]) {
                if ($ligne !== null) {
                    $lignes[] = $ligne;
                }
            }
        }

        return $lignes;
    }

    /** @return iterable<array{0: string, 1: Ligne|null}> */
    private function lire(string $chemin): iterable
    {
        if (!is_file($chemin)) {
            return;
        }
        foreach (file($chemin, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $brut) {
            yield [$brut, $this->decoder($brut)];
        }
    }

    /** @return Ligne|null */
    private function decoder(string $brut): ?array
    {
        try {
            $ligne = json_decode($brut, true, 512, JSON_THROW_ON_ERROR);
            if (!\is_array($ligne) || !isset($ligne['empreinte'], $ligne['date']) || !\is_string($ligne['date'])) {
                return null;
            }
            $ligne['_date'] = new \DateTimeImmutable($ligne['date']);

            return $ligne;
        } catch (\Throwable) {
            return null;
        }
    }
}
