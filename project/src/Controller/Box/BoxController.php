<?php

namespace App\Controller\Box;

use App\Repository\Box\ObjetRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/box')]
class BoxController extends AbstractController
{
    #[Route('/', name: 'app_box')]
    public function index(ObjetRepository $objetRepository): Response
    {

        return $this->render('box/index.html.twig', [
            'objets' => $objetRepository->findAll(),
        ]);
    }
}
