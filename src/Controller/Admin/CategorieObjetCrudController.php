<?php

namespace App\Controller\Admin;

use App\Entity\Box\CategorieObjet;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class CategorieObjetCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CategorieObjet::class;
    }


    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('nom');
    }

}
