<?php

namespace App\Command;

use App\Service\Sauvegarde;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/** Sauvegarde de la base, puis nettoyage des anciennes (cf. Sauvegarde) */
#[AsCommand(name: 'app:sauvegarder', description: 'Sauvegarde la base dans var/sauvegardes et retire les anciennes')]
class SauvegarderCommand
{
    public function __construct(private Sauvegarde $sauvegarde)
    {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option(description: 'Copie à part, hors rotation quotidienne (deploiement)')]
        ?string $raison = null,
    ): int {
        try {
            $fichier = $this->sauvegarde->sauvegarder($raison);
        } catch (\InvalidArgumentException $e) {
            $io->error($e->getMessage());

            return Command::INVALID;
        }
        $io->success(\sprintf('Sauvegarde : %s (%d Ko)', \basename($fichier), (int) (\filesize($fichier) / 1024)));
        foreach ($this->sauvegarde->nettoyer() as $supprime) {
            $io->writeln('Supprimée : ' . $supprime);
        }

        return Command::SUCCESS;
    }
}
