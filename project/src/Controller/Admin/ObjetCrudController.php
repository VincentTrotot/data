<?php

namespace App\Controller\Admin;

use App\Entity\Box\Objet;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class ObjetCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Objet::class;
    }


    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('nom');
        yield AssociationField::new('categorie');
        yield AssociationField::new('carton');
    }

}
