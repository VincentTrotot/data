<?php

namespace App\Controller\Admin;

use App\Entity\Carburant\Voiture;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class VoitureCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Voiture::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('marque');
        yield TextField::new('modele');
        yield TextField::new('immatriculation');
    }

}
