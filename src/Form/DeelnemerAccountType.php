<?php
declare(strict_types = 1);

namespace App\Form;

use App\Entity\Deelnemer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DeelnemerAccountType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user_id')
            ->add('bondsnummer')
            ->add('naam')
            ->add('geb_datum')
            ->add('ranking_enkel')
            ->add('ranking_dubbel')
            ->add('telefoonnummer')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Deelnemer::class,
        ]);
    }
}
