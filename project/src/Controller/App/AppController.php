<?php

namespace App\Controller\App;

use App\Entity\Carburant\Plein;
use App\Repository\Carburant\PleinRepository;
use App\Repository\Carburant\VoitureRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AppController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(PleinRepository $pleinRepository): Response
    {
        $pleins = $pleinRepository->findAllByDate('ASC');
        $labels = [];
        $data = [];
        foreach ($pleins as $plein) {
            $labels[] = $plein->getDate()->format('m/Y');
            $data[] = $plein->getPrixAuLitre();
        }
        return $this->render('app/index.html.twig', [
            'pleins' => $pleins,
            'data' => json_encode($data),
            'labels' => json_encode($labels),
        ]);
    }

    

}
