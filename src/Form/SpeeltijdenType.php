<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Speeltijden;

class SpeeltijdenType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', null, array('required' => false))
            ->add('toernooi_id')
            ->add('dagnummer', null, ['attr' => ['readonly' => true],])
            ->add('wedstrijd_duur')
            ->add('starttijd')
            ->add('eindtijd');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Speeltijden::class,
        ]);
    }
}
