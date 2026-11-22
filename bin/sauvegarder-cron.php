<?php

/*
 * Tâche de nuit (cron OVH, crontab d'un serveur… cf. docs/deploiement.md) : un fichier PHP lancé sans arguments, qui
 * lance app:sauvegarder et écrit leur compte rendu dans
 * var/sauvegardes/derniere-execution.json (page « Sauvegardes » de l'admin).
 */

use App\Kernel;
use App\Service\Sauvegarde;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Bundle\FrameworkBundle\Console\Application;

require dirname(__DIR__) . '/vendor/autoload_runtime.php';

return function (array $context) {
    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
    $application = new Application($kernel);
    $application->setAutoExit(false);
    // pour récupérer le message d'une exception au lieu de son affichage
    $application->setCatchExceptions(false);

    return function () use ($application, $kernel): int {
        $sortie = new ConsoleOutput();
        $maintenant = fn (): string => (new DateTimeImmutable('now', new DateTimeZone('Europe/Paris')))
            ->format(DateTimeInterface::ATOM);
        $lancer = function (string $commande) use ($application, $sortie): array {
            try {
                return ['code' => $application->run(new ArrayInput(['command' => $commande]), $sortie), 'erreur' => null];
            } catch (Throwable $e) {
                $classe = substr(strrchr('\\' . $e::class, '\\') ?: '', 1);

                return ['code' => 1, 'erreur' => mb_substr($classe . ': ' . $e->getMessage(), 0, 500)];
            }
        };

        $debut = $maintenant();
        $sauvegarde = $lancer('app:sauvegarder');
        $compteRendu = [
            'debut' => $debut,
            'fin' => $maintenant(),
            'sauvegarde' => $sauvegarde,
        ];
        try {
            $sauvegardes = $kernel->getContainer()->get(Sauvegarde::class);
            assert($sauvegardes instanceof Sauvegarde);
            $sauvegardes->ecrireCompteRendu($compteRendu);
        } catch (Throwable $e) {
            fwrite(STDERR, 'Compte rendu non écrit : ' . $e->getMessage() . PHP_EOL);
        }

        return $sauvegarde['code'];
    };
};
