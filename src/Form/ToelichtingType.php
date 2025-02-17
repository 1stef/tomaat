<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Bericht;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ToelichtingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titel')
            ->add('tekst', TextareaType::class)
            ->add('wedstrijd_wijziging_id')
            ->add('afzender')
            ->add('ontvanger_1')
            ->add('ontvanger_2')
            ->add('ontvanger_3')
            ->add('berichttype');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Bericht::class,
        ]);
    }
}
