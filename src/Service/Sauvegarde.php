<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Sauvegarde quotidienne de la base SQLite (commande app:sauvegarder, lancée chaque nuit par une tâche planifiée,
 * cf. bin/sauvegarder-cron.php) : une copie cohérente (VACUUM INTO, même pendant une écriture), compressée et datée
 * dans var/sauvegardes/. On garde les 14 derniers jours et la première copie de chaque mois sur un an ;
 * `make sauvegarde` les rapatrie hors du serveur. Les copies faites avant un déploiement
 * (data-AAAA-MM-JJ-HHMM-deploiement.db.gz, `app:sauvegarder --raison=deploiement`) sont à part et purgées après
 * 30 jours par purgerDeploiements() (à appeler depuis app:donnees:purger).
 */
// public : bin/sauvegarder-cron.php y écrit son compte rendu depuis le conteneur
#[Autoconfigure(public: true)]
class Sauvegarde
{
    public const JOURS = 14;
    public const MOIS = 12;
    public const JOURS_DEPLOIEMENT = 30;
    // à adapter : préfixe des fichiers (nom de l'appli), repris dans les deux motifs et dans bin/sauvegardes
    public const PREFIXE = 'data';
    private const COMPTE_RENDU = 'derniere-execution.json';
    private const MOTIF = '/^data-(\d{4}-\d{2}-\d{2})\.db\.gz$/';
    private const MOTIF_DEPLOIEMENT = '/^data-(\d{4}-\d{2}-\d{2})-\d{4}-deploiement\.db\.gz$/';

    public function __construct(
        private Connection $connection,
        private ClockInterface $clock,
        #[Autowire('%kernel.project_dir%/var/sauvegardes')]
        private string $dossier,
    ) {
    }

    /**
     * @param string|null $raison null pour la copie du jour ; « deploiement » pour une copie horodatée gardée
     *                            hors de la rotation quotidienne
     *
     * @return string le fichier créé (ou celui du jour, déjà là)
     */
    public function sauvegarder(?string $raison = null): string
    {
        if ($raison !== null && $raison !== 'deploiement') {
            throw new \InvalidArgumentException('Raison inconnue : ' . $raison);
        }
        $this->creerDossier();
        $maintenant = $this->clock->now();
        $fichier = $raison === null
            ? \sprintf('%s/%s-%s.db.gz', $this->dossier, self::PREFIXE, $maintenant->format('Y-m-d'))
            : \sprintf('%s/%s-%s-%s.db.gz', $this->dossier, self::PREFIXE, $maintenant->format('Y-m-d-Hi'), $raison);
        if (\is_file($fichier)) {
            return $fichier;
        }

        $copie = $this->dossier . '/copie-en-cours.db';
        if (\is_file($copie)) {
            \unlink($copie);
        }
        $this->connection->executeStatement('VACUUM INTO ?', [$copie]);
        $this->compresser($copie, $fichier . '.partiel');
        \unlink($copie);
        // le nom définitif seulement une fois le fichier complet : make sauvegarde ne rapatrie pas une copie partielle
        \rename($fichier . '.partiel', $fichier);
        \chmod($fichier, 0o600);

        return $fichier;
    }

    /**
     * Supprime les copies quotidiennes qui ne sont ni des 14 derniers jours, ni la première d'un des 12 derniers mois
     *
     * @return list<string> fichiers supprimés
     */
    public function nettoyer(): array
    {
        $aujourdhui = \DateTimeImmutable::createFromInterface($this->clock->now())->setTime(0, 0);
        $limiteJours = $aujourdhui->modify(\sprintf('-%d days', self::JOURS - 1))->format('Y-m-d');
        $limiteMois = $aujourdhui->modify(\sprintf('first day of -%d months', self::MOIS - 1))->format('Y-m');

        $parMois = [];
        foreach (\scandir($this->dossier) ?: [] as $nom) {
            if (\preg_match(self::MOTIF, $nom, $date) === 1) {
                $parMois[\substr($date[1], 0, 7)][$date[1]] = $nom;
            }
        }

        $supprimes = [];
        foreach ($parMois as $mois => $copies) {
            \ksort($copies);
            $premiere = \array_key_first($copies);
            foreach ($copies as $jour => $nom) {
                $garder = $jour >= $limiteJours || ($jour === $premiere && $mois >= $limiteMois);
                if (!$garder) {
                    \unlink($this->dossier . '/' . $nom);
                    $supprimes[] = $nom;
                }
            }
        }

        return $supprimes;
    }

    /**
     * Supprime les copies de déploiement de plus de 30 jours
     *
     * @return list<string> fichiers supprimés
     */
    public function purgerDeploiements(): array
    {
        $limite = \DateTimeImmutable::createFromInterface($this->clock->now())
            ->setTime(0, 0)
            ->modify(\sprintf('-%d days', self::JOURS_DEPLOIEMENT))
            ->format('Y-m-d');

        $supprimes = [];
        foreach (\is_dir($this->dossier) ? (\scandir($this->dossier) ?: []) : [] as $nom) {
            if (\preg_match(self::MOTIF_DEPLOIEMENT, $nom, $date) === 1 && $date[1] < $limite) {
                \unlink($this->dossier . '/' . $nom);
                $supprimes[] = $nom;
            }
        }

        return $supprimes;
    }

    /**
     * Compte rendu de la dernière tâche de nuit (bin/sauvegarder-cron.php), écrit à côté des copies
     *
     * @param array<string, mixed> $compteRendu
     */
    public function ecrireCompteRendu(array $compteRendu): void
    {
        $this->creerDossier();
        $fichier = $this->dossier . '/' . self::COMPTE_RENDU;
        $temporaire = $fichier . '.tmp';
        \file_put_contents($temporaire, \json_encode($compteRendu, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT));
        \chmod($temporaire, 0o600);
        \rename($temporaire, $fichier);
    }

    /** @return array<string, mixed>|null null si absent ou illisible */
    public function compteRendu(): ?array
    {
        $contenu = @\file_get_contents($this->dossier . '/' . self::COMPTE_RENDU);
        if ($contenu === false) {
            return null;
        }
        try {
            $donnees = \json_decode($contenu, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return \is_array($donnees) ? \array_combine(\array_map('strval', \array_keys($donnees)), $donnees) : null;
    }

    /**
     * Ce que voit l'admin : les copies quotidiennes du serveur, le dernier compte rendu et, s'il y a lieu, l'alerte
     *
     * @return array{derniere: ?\DateTimeImmutable, taille: ?int, copies: list<array{nom: string, date: \DateTimeImmutable, taille: int}>, compteRendu: ?array<string, mixed>, alerte: ?string}
     */
    public function etat(\DateTimeImmutable $maintenant): array
    {
        $copies = [];
        foreach (\is_dir($this->dossier) ? (\scandir($this->dossier) ?: []) : [] as $nom) {
            if (\preg_match(self::MOTIF, $nom, $date) === 1) {
                $copies[] = [
                    'nom' => $nom,
                    'date' => new \DateTimeImmutable($date[1]),
                    'taille' => (int) \filesize($this->dossier . '/' . $nom),
                ];
            }
        }
        \usort($copies, fn (array $a, array $b) => $b['date'] <=> $a['date']);
        $derniere = $copies[0] ?? null;
        $compteRendu = $this->compteRendu();

        $alerte = null;
        if ($derniere === null) {
            $alerte = 'Aucune sauvegarde sur le serveur : la tâche planifiée est-elle en place ?';
        } elseif ($derniere['date'] < $maintenant->setTime(0, 0)->modify('-1 day')) {
            $alerte = \sprintf(
                'Pas de sauvegarde depuis le %s : vérifie la tâche planifiée.',
                (new DatesEnLettres())->jour($derniere['date'])
            );
        } elseif ($compteRendu !== null) {
            $echecs = [];
            foreach (['sauvegarde', 'purge'] as $commande) {
                $resultat = $compteRendu[$commande] ?? null;
                if (\is_array($resultat) && ($resultat['code'] ?? 0) !== 0) {
                    $erreur = \is_string($resultat['erreur'] ?? null) ? $resultat['erreur'] : 'code ' . \var_export($resultat['code'], true);
                    $echecs[] = $commande . ' : ' . $erreur;
                }
            }
            if ($echecs !== []) {
                $alerte = \sprintf('La dernière tâche de nuit a échoué (%s).', \implode(' ; ', $echecs));
            }
        }

        return [
            'derniere' => $derniere['date'] ?? null,
            'taille' => $derniere['taille'] ?? null,
            'copies' => $copies,
            'compteRendu' => $compteRendu,
            'alerte' => $alerte,
        ];
    }

    private function creerDossier(): void
    {
        if (!\is_dir($this->dossier) && !\mkdir($this->dossier, 0o700, true) && !\is_dir($this->dossier)) {
            throw new \RuntimeException('Impossible de créer ' . $this->dossier);
        }
    }

    private function compresser(string $source, string $destination): void
    {
        $entree = \fopen($source, 'rb');
        $sortie = \gzopen($destination, 'wb9');
        if ($entree === false || $sortie === false) {
            throw new \RuntimeException('Impossible de compresser la sauvegarde');
        }
        while (!\feof($entree)) {
            \gzwrite($sortie, (string) \fread($entree, 1 << 20));
        }
        \fclose($entree);
        \gzclose($sortie);
    }
}
