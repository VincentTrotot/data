<?php

namespace App\Controller\Admin;

use App\Entity\Box\Carton;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class CartonCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Carton::class;
    }


    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('numero');
        yield TextField::new('emplacement');
    }
    
}
