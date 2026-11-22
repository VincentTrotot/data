<?php

namespace App\Command;

use App\Entity\Security\Utilisateur;
use App\Repository\Security\UtilisateurRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:utilisateur:creer', description: 'Crée un compte (les inscriptions publiques sont fermées)')]
class CreerUtilisateurCommand extends Command
{
    public function __construct(
        private readonly UtilisateurRepository $utilisateurs,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Adresse e-mail (identifiant)')
            ->addArgument('prenom', InputArgument::REQUIRED)
            ->addArgument('nom', InputArgument::REQUIRED)
            ->addOption('admin', null, InputOption::VALUE_NONE, 'Donne le rôle ROLE_ADMIN')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');

        if (null !== $this->utilisateurs->findOneBy(['email' => $email])) {
            $io->error('Un compte existe déjà avec cet e-mail.');

            return Command::FAILURE;
        }

        $motDePasse = $io->askHidden('Mot de passe (6 caractères minimum)');
        if (null === $motDePasse || mb_strlen($motDePasse) < 6) {
            $io->error('Mot de passe trop court.');

            return Command::FAILURE;
        }

        $utilisateur = (new Utilisateur())
            ->setEmail($email)
            ->setPrenom($input->getArgument('prenom'))
            ->setNom($input->getArgument('nom'))
            ->setRoles($input->getOption('admin') ? ['ROLE_ADMIN'] : []);
        $utilisateur->setPassword($this->hasher->hashPassword($utilisateur, $motDePasse));

        $this->utilisateurs->add($utilisateur, true);

        $io->success(sprintf('Compte %s créé.', $email));

        return Command::SUCCESS;
    }
}
