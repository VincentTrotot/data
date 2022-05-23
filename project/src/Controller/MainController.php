<?php

namespace App\Controller;

use App\Entity\Plein;
use App\Repository\PleinRepository;
use App\Repository\VoitureRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_main')]
    public function index(PleinRepository $pleinRepository): Response
    {
        $pleins = $pleinRepository->findAllByDate('ASC');
        $labels = [];
        $data = [];
        foreach ($pleins as $plein) {
            $labels[] = $plein->getDate()->format('m/Y');
            $data[] = $plein->getPrixAuLitre();
        }
        return $this->render('main/index.html.twig', [
            'pleins' => $pleins,
            'data' => json_encode($data),
            'labels' => json_encode($labels),
        ]);
    }

    #[Route('/api/plein/add/{quantite}/{prix}/{kilometrage}/{voiture}', name: 'app_api_plein_add', methods: ['GET'])]
    public function add(PleinRepository $pleinRepository, VoitureRepository $voitureRepository, $quantite, $prix, $kilometrage, $voiture): Response
    {
        $voiture_ = $voitureRepository->find($voiture);
        $plein = new Plein();
        $plein->setDate(new \DateTime());
        $plein->setQuantite($quantite);
        $plein->setPrix($prix);
        $plein->setKilometrage($kilometrage);
        $plein->setVoiture($voiture_);

        $pleinRepository->add($plein, true);

        return $this->redirectToRoute('app_main', [], Response::HTTP_SEE_OTHER);
    }
}
