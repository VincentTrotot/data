<?php

namespace App\Controller;

use App\Entity\Plein;
use App\Entity\Voiture;
use App\Form\PleinType;
use App\Repository\PleinRepository;
use App\Repository\VoitureRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/plein')]
class PleinController extends AbstractController
{
    #[Route('/', name: 'app_plein_index', methods: ['GET'])]
    public function index(PleinRepository $pleinRepository): Response
    {
        return $this->render('plein/index.html.twig', [
            'pleins' => $pleinRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_plein_new', methods: ['GET', 'POST'])]
    public function new(Request $request, PleinRepository $pleinRepository): Response
    {
        $plein = new Plein();
        $form = $this->createForm(PleinType::class, $plein);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pleinRepository->add($plein, true);

            return $this->redirectToRoute('app_plein_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('plein/new.html.twig', [
            'plein' => $plein,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_plein_show', methods: ['GET'])]
    public function show(Plein $plein): Response
    {
        return $this->render('plein/show.html.twig', [
            'plein' => $plein,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_plein_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Plein $plein, PleinRepository $pleinRepository): Response
    {
        $form = $this->createForm(PleinType::class, $plein);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pleinRepository->add($plein, true);

            return $this->redirectToRoute('app_plein_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('plein/edit.html.twig', [
            'plein' => $plein,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_plein_delete', methods: ['POST'])]
    public function delete(Request $request, Plein $plein, PleinRepository $pleinRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$plein->getId(), $request->request->get('_token'))) {
            $pleinRepository->remove($plein, true);
        }

        return $this->redirectToRoute('app_plein_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/add/{quantite}/{prix}/{kilometrage}/{voiture}', name: 'app_plein_add', methods: ['GET'])]
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

        return $this->redirectToRoute('app_plein_index', [], Response::HTTP_SEE_OTHER);
    }
}
