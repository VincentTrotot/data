<?php

namespace App\Controller\Admin;

use App\Entity\Objet;
use App\Entity\Plein;
use App\Entity\Carton;
use App\Entity\Station;
use App\Entity\Voiture;
use App\Entity\Mouvement;
use App\Entity\Utilisateur;
use App\Entity\CategorieObjet;
use App\Controller\Admin\PleinCrudController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        //return parent::index();

        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
        // $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        // return $this->redirect(
        //     $adminUrlGenerator
        //         ->setController(PleinCrudController::class)
        //         ->generateUrl()
        //     );

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirect('...');
        // }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
        
        return $this->render('admin/index.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Administration de vt_data');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToRoute('Accueil', 'fa fa-home', 'app_main');
        yield MenuItem::linkToDashboard('Administration', 'fa fa-shield-alt');
        
        yield MenuItem::section('Carburant');
        yield MenuItem::linkToCrud('Pleins', 'fa fa-tachometer-alt', Plein::class);
        yield MenuItem::linkToCrud('Voitures', 'fa fa-car', Voiture::class);
        yield MenuItem::linkToCrud('Stations', 'fa fa-gas-pump', Station::class);
        
        yield MenuItem::section('Box');
        yield MenuItem::linkToCrud('Objets', 'fa fa-cubes', Objet::class);
        yield MenuItem::linkToCrud('Catégories d\'objet', 'fa fa-info-circle', CategorieObjet::class);
        yield MenuItem::linkToCrud('Cartons', 'fa fa-box-open', Carton::class);
        yield MenuItem::linkToCrud('Mouvements', 'fa fa-exchange-alt', Mouvement::class);
        
        yield MenuItem::section('Utilisateurs');
        yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-user', Utilisateur::class);
    }
}
