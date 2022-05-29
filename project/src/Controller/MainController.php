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

    

}
