<?php

namespace App\Controller\Box;

use App\Entity\Box\Objet;
use App\Entity\Box\Mouvement;
use App\Repository\Box\ObjetRepository;
use App\Repository\Box\MouvementRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/box')]
class BoxController extends AbstractController
{
    #[Route('/', name: 'box_index')]
    public function index(ObjetRepository $objetRepository): Response
    {

        return $this->render('box/index.html.twig', [
            'objets' => $objetRepository->findAll(),
        ]);
    }

    #[Route('/objet/retirer/{id}', name: 'box_objet_retirer')]
    public function addObjet(MouvementRepository $mouvementRepository, Objet $objet): Response
    {
        $lastMouvement = $mouvementRepository->findLastForObjet($objet);
        if($lastMouvement->getSens() == 'in') {
            // On ajoute un mouvement pour sortir l'objet
            $mouvement = new Mouvement();
            $mouvement->setObjet($objet);
            $mouvement->setSens('out');
            $mouvement->setDate(new \DateTime());
            $mouvement->setUtilisateur($this->getUser());

            $mouvementRepository->add($mouvement, true);

        } else {
            // On met à jour la date de sortie de l'objet à aujourd'hui
            $lastMouvement->setDate(new \DateTime());
            $mouvementRepository->add($lastMouvement, true);
        }
        return $this->redirectToRoute('box_index');
    }

    #[Route('/objet/ajouter/{id}', name: 'box_objet_ajouter')]
    public function ajouterObjet(MouvementRepository $mouvementRepository, Objet $objet)
    {
        $lastMouvement = $mouvementRepository->findLastForObjet($objet);
        if($lastMouvement->getSens() == 'out') {
            // On ajoute un mouvement pour sortir l'objet
            $mouvement = new Mouvement();
            $mouvement->setObjet($objet);
            $mouvement->setSens('in');
            $mouvement->setDate(new \DateTime());
            $mouvement->setUtilisateur($this->getUser());

            $mouvementRepository->add($mouvement, true);

        } else {
            // On met à jour la date de sortie de l'objet à aujourd'hui
            $lastMouvement->setDate(new \DateTime());
            $mouvementRepository->add($lastMouvement, true);
        }
        return $this->redirectToRoute('box_index');
    }

}
