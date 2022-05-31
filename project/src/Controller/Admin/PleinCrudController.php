<?php

namespace App\Controller\Admin;

use App\Entity\Carburant\Plein;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class PleinCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Plein::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Plein')
            ->setEntityLabelInPlural('Pleins')
            ->setPageTitle(Crud::PAGE_INDEX, 'Pleins')
            ->setDefaultSort(['date' => 'DESC']);
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
            IdField::new('id')->hideOnForm()->hideOnIndex(),
            DateTimeField::new('date')->setFormat('EEEE d MMMM yyyy - HH:mm'),
            IntegerField::new('quantite')
                ->formatValue(function ($value) {
                    return number_format($value / 100, 2, '.', ' ') . 'L';
                }
            ),
            IntegerField::new('prix')
            ->formatValue(function ($value) {
                return number_format($value / 100, 2, '.', ' ') . '€';
            }
        ),
            IntegerField::new('kilometrage')
                ->formatValue(function($value) {
                    return number_format($value, 0, '.', ' ') . ' km';
                }),
            NumberField::new('prixAuLitre')
                ->hideOnForm()
                ->formatValue(function ($value) {
                    return $value . '€';
                }
            ),
            AssociationField::new('voiture'),
            AssociationField::new('station'),
        ];
    }
}
