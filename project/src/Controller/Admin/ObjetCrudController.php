<?php

namespace App\Controller\Admin;

use App\Entity\Objet;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class ObjetCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Objet::class;
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
