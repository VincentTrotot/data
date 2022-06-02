<?php

namespace App\Controller\Box;

use App\Entity\Box\Objet;
use App\Entity\Box\Carton;
use App\Form\Box\ObjetType;
use App\Entity\Box\Mouvement;
use App\Entity\Box\CategorieObjet;
use App\Repository\Box\ObjetRepository;
use App\Repository\Box\CartonRepository;
use App\Repository\Box\MouvementRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Box\CategorieObjetRepository;
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

    #[Route('/objet/replacer/{id}', name: 'box_objet_replacer')]
    public function replacerObjet(MouvementRepository $mouvementRepository, Objet $objet)
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

    #[Route('/objet/ajouter', name: 'box_objet_ajouter')]
    public function ajouterObjet(
        Request $request,
        ObjetRepository $objetRepository,
        MouvementRepository $mouvementRepository,
        CartonRepository $cartonRepository,
        CategorieObjetRepository $categorieObjetRepository
    ) : Response
    {
        $objet = new Objet();
        $error = false;
        $form = $this->createForm(ObjetType::class, $objet);
        $form->handleRequest($request);

        // Si le formulaire n'est pas soumis ou n'est pas valide,
        // on affiche la vue du formulaire
        if (!($form->isSubmitted() && $form->isValid())) {
            return $this->render('box/ajouter.html.twig', [
                'form' => $form->createView(),
            ]);
        }
        $categorie = $objet->getCategorie();
        $categorie_add = $request->request->all()['objet']['categorie_add'];
        $carton = $objet->getCarton();
        $carton_add = $request->request->all()['objet']['carton_add'];

        // Gestion des erreurs
        if($categorie == null && $categorie_add['nom'] == null) {
            
            $this->addFlash('error', 'Vous devez choisir une catégorie ou entrer un nom de catégorie');
            $error = true;
        }
        if($carton == null && $carton_add['numero'] == null) {
                $this->addFlash('error', 'Vous devez choisir un carton ou entrer un numéro de carton');
                $error = true;
        }
        if($error) {
            return $this->render('box/ajouter.html.twig', [
                'form' => $form->createView(),
            ]);
        }

        // Si on arrive jusqu'ici, c'est qu'il n'y a pas d'erreur

        // Gestion de la catégorie
        if($categorie == null) {
            $categorie = $categorieObjetRepository->findOneBy(['nom' => $categorie_add['nom']]);
            if($categorie == null) {
                $categorie = new CategorieObjet();
                $categorie->setNom($categorie_add['nom']);
            }
        }
        $categorieObjetRepository->add($categorie, true);
        $objet->setCategorie($categorie);
        
        //Gestion du carton
        if($carton == null){
            $carton = $cartonRepository->findOneBy(['numero' => $carton_add['numero']]);
            if($carton == null) {
                $carton = new Carton();
                $carton->setNumero($carton_add['numero']);
                $carton->setEmplacement($carton_add['emplacement']);
            }
            $cartonRepository->add($carton, true);
        }
        $objet->setCarton($carton);

        // Enregistrement de l'objet
        $objetRepository->add($objet, true);

        // Enregistrement du mouvement
        $mouvement = new Mouvement();
        $mouvement->setObjet($objet);
        $mouvement->setSens('in');
        $mouvement->setDate(new \DateTime());
        $mouvement->setUtilisateur($this->getUser());
        $mouvementRepository->add($mouvement, true);

        return $this->redirectToRoute('box_index');
    }

       

}
