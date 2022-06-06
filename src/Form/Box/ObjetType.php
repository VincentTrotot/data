<?php

namespace App\Form\Box;

use App\Entity\Box\Objet;
use App\Entity\Box\Carton;
use App\Form\Box\CartonType;
use App\Entity\Box\CategorieObjet;
use App\Form\Box\CategorieObjetType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ObjetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add(
                'categorie',
                EntityType::class,
                [
                    'class' => CategorieObjet::class,
                    'choice_label' => 'nom',
                    'placeholder' => 'Choisissez une catégorie',
                    'required' => false,
                ]
            )
            ->add(
                'carton',
                EntityType::class,
                [
                    'class' => Carton::class,
                    'choice_label' => 'numero',
                    'placeholder' => 'Choisissez un carton',
                    'required' => false,
                ]
            )
            ->add(
                $builder->create(
                    'categorie_add',
                    CategorieObjetType::class,
                    [
                        'mapped' => false,
                        'label' => 'Ajouter une catégorie',
                        'required' => false,
                    ]
                )
                ->add('nom')
            )
            ->add(
                $builder->create(
                    'carton_add',
                    CartonType::class,
                    [
                        'mapped' => false,
                        'label' => 'Ajouter un carton',
                        'required' => false,
                    ]
                )
                ->add('numero')
                ->add('emplacement')
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Objet::class,
        ]);
    }
}
