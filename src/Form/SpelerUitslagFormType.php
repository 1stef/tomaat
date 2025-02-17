<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SpelerUitslagFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('baan', null, ['disabled' => true])
            ->add('set1_team1')
            ->add('set1_team2')
            ->add('set2_team1')
            ->add('set2_team2')
            ->add('set3_team1')
            ->add('set3_team2')
            ->add('winnaar', ChoiceType::class, ['choices' => ['Team 1' => 1, 'Team 2' => 2,], 'disabled' => true])
            ->add('opgave', ChoiceType::class, ['choices' => ['nee' => 0, 'Team 1' => 1, 'Team 2' => 2,],])
            ->add(
                'wedstrijd_status',
                ChoiceType::class,
                ['choices' => ['gespeeld' => 'gespeeld', 'onderbroken' => 'onderbroken',],]
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UitslagFormData::class,
        ]);
    }
}
