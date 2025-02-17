<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Toernooi;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AanvraagFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('toernooi_naam')
            ->add('vereniging')
            ->add('aantal_dagen')
            ->add('eerste_dag', DateType::class, ['widget' => 'single_text', 'input' => 'datetime', 'required' => true])
            // TODO: configure a javascript datetime picker
            ->add('aanvrager_naam')
            ->add('aanvrager_tel')
            ->add('aanvrager_email')
            ->add('opmerkingen');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Toernooi::class,
        ]);
    }
}
