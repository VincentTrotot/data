<?php

namespace App\Controller\Admin;

use App\Config\Sens;
use App\Entity\Mouvement;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
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
        return [
            IdField::new('id')->hideOnDetail()->hideOnForm(),
            DateTimeField::new('date'),
            ChoiceField::new('sens')
                ->setChoices([
                    'Dépôt' => 'in',
                    'Retrait' => 'out',
                ]),
            AssociationField::new('objet'),
            AssociationField::new('utilisateur'),
        ];
    }
    
}
