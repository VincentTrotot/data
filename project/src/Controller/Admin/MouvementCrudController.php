<?php

namespace App\Controller\Admin;

use App\Entity\Box\Mouvement;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class MouvementCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Mouvement::class;
    }

    public function createEntity(string $entityFqcn)
    {
        $entity = new $entityFqcn();
        $entity->setDate(new \DateTime());
        return $entity;
    }

    
    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('objet');
        yield ChoiceField::new('sens')
            ->setChoices([
                'Dépôt' => 'in',
                'Retrait' => 'out',
            ]);
        yield DateTimeField::new('date');
        yield AssociationField::new('utilisateur');
    }
    
}
