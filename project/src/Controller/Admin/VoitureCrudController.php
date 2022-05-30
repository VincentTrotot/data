<?php

namespace App\Controller\Admin;

use App\Entity\Voiture;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class VoitureCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Voiture::class;
    }

    /*
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
    }
    */
}
