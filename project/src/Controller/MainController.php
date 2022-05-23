<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\PleinRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_main')]
    public function index(PleinRepository $pleinRepository): Response
    {
        $pleins = $pleinRepository->findAllByDate('ASC');
        $labels = [];
        $data = [];
        foreach ($pleins as $plein) {
            $labels[] = $plein->getDate()->format('d/m/Y');
            $data[] = $plein->getPrixAuLitre();
        }
        return $this->render('main/index.html.twig', [
            'pleins' => $pleins,
            'data' => json_encode($data),
            'labels' => json_encode($labels),
        ]);
    }

    #[Route('/role/{id}', name: 'app_role')]
    public function db(ManagerRegistry $doctrine, int $id): Response
    {
        $entityManager = $doctrine->getManager();
        $utilisateur = $entityManager->getRepository(Utilisateur::class)->find($id);
        
        $utilisateur->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $entityManager->flush();
        return $this->redirectToRoute('app_main');
    }
}
