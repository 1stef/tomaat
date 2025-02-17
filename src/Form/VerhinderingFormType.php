<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Verhindering;
use App\Repository\ToernooiRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VerhinderingFormType extends AbstractType
{
    private array $dagenLijst;

    public function __construct(RequestStack $requestStack, ToernooiRepository $toernooiRepository)
    {
        $toernooi_id = $requestStack->getSession()->get("huidig_toernooi_id");
        $toernooi = $toernooiRepository->find($toernooi_id);
        $timestamp = $toernooi->getEersteDag()->getTimestamp();
        setlocale(LC_ALL, 'nld_nld');
        $this->dagenLijst = [];
        for ($i = 0; $i < $toernooi->getAantalDagen(); $i++) {
            $datum_string = strftime('%A %e %b %G', $timestamp);
            $this->dagenLijst[$datum_string] = $i + 1;
            $timestamp = strtotime('+1 days', $timestamp);
        }
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', IntegerType::class, ['required' => false])
            ->add('toernooi_id')
            ->add('bondsnr')
            ->add(
                'dagnummer',
                ChoiceType::class, ['choices' => $this->dagenLijst]
            )
            ->add('celnummer')
            ->add('begintijd')
            ->add('eindtijd')
            ->add('hele_dag');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Verhindering::class,
        ]);
    }
}
