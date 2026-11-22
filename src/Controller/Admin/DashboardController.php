<?php

namespace App\Controller\Admin;

use App\Entity\Box\Objet;
use App\Entity\Carburant\Plein;
use App\Entity\Box\Carton;
use App\Entity\Carburant\Station;
use App\Entity\Carburant\Voiture;
use App\Entity\Box\Mouvement;
use App\Entity\Security\Utilisateur;
use App\Entity\Box\CategorieObjet;
use App\Repository\Box\CartonRepository;
use App\Repository\Box\CategorieObjetRepository;
use App\Repository\Box\ObjetRepository;
use App\Repository\Carburant\PleinRepository;
use App\Repository\Carburant\StationRepository;
use App\Repository\Carburant\VoitureRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

class DashboardController extends AbstractDashboardController
{

    private $objetRepository;
    private $cartonRepository;
    private $categorieObjetRepository;
    private $pleinRepository;
    private $stationRepository;
    private $voitureRepository;

    public function __construct(
        ObjetRepository $objetRepository, 
        CartonRepository $cartonRepository, 
        CategorieObjetRepository $categorieObjetRepository, 
        PleinRepository $pleinRepository, 
        StationRepository $stationRepository, 
        VoitureRepository $voitureRepository
    ){
        $this->objetRepository = $objetRepository;
        $this->cartonRepository = $cartonRepository;
        $this->categorieObjetRepository = $categorieObjetRepository;
        $this->pleinRepository = $pleinRepository;
        $this->stationRepository = $stationRepository;
        $this->voitureRepository = $voitureRepository;
    }

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {

        return $this->render('admin/index.html.twig',
            [
                'total_objets' => $this->objetRepository->count([]),
                'total_cartons' => $this->cartonRepository->count([]),
                'total_categories' => $this->categorieObjetRepository->count([]),
                'total_pleins' => $this->pleinRepository->count([]),
                'total_stations' => $this->stationRepository->count([]),
                'total_voitures' => $this->voitureRepository->count([]),
            ]
        );
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Administration de vt_data');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToUrl('Accueil', 'fa fa-home', '/');
        yield MenuItem::linkToUrl('Administration', 'fa fa-shield-alt', '/admin');
        
        yield MenuItem::section('Carburant');
        yield MenuItem::linkTo(PleinCrudController::class, 'Pleins', 'fa fa-tachometer-alt');
        yield MenuItem::linkTo(VoitureCrudController::class, 'Voitures', 'fa fa-car');
        yield MenuItem::linkTo(StationCrudController::class, 'Stations', 'fa fa-gas-pump');
        
        yield MenuItem::section('Box');
        yield MenuItem::linkToUrl('Ajouter un objet', 'fa fa-plus', '/admin/box/objet/ajouter');
        yield MenuItem::linkTo(ObjetCrudController::class, 'Objets', 'fa fa-cubes');
        yield MenuItem::linkTo(CategorieObjetCrudController::class, 'Catégories d\'objet', 'fa fa-info-circle');
        yield MenuItem::linkTo(CartonCrudController::class, 'Cartons', 'fa fa-box-open');
        yield MenuItem::linkTo(MouvementCrudController::class, 'Mouvements', 'fa fa-exchange-alt');
        
        yield MenuItem::section('Utilisateurs');
        yield MenuItem::linkTo(UtilisateurCrudController::class, 'Utilisateurs', 'fa fa-user');
    }
}
