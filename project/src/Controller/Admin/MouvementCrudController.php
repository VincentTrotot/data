<?php

namespace App\Controller\Admin;

use App\Entity\Mouvement;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class MouvementCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Mouvement::class;
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
