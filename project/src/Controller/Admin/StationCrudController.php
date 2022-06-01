<?php

namespace App\Controller\Admin;

use App\Entity\Carburant\Station;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class StationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Station::class;
    }


    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('nom');
        yield TextField::new('ville');
    }

}
