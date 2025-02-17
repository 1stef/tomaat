<?php
declare(strict_types = 1);

namespace App\Form;

use App\Repository\CategorieRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;


class InschrijvingType extends AbstractType
{
    private array $catList;
    private array $catTypes;
    
    public function __construct(RequestStack $requestStack, CategorieRepository $categorieRepository)
    {
        $toernooi_id = $requestStack->getSession()->get("huidig_toernooi_id");
        $categories = $categorieRepository->findBy(["toernooi_id" => $toernooi_id]);
        $this->catList = [];
        $this->catTypes = [];
        $this->catList['Kies categorie'] = 'geen categorie';
        foreach ($categories as $category) {
            $this->catList[$category->getCat()] = $category->getCat();
            $this->catTypes[$category->getCat()] = ['data-cat-type' => $category->getCatType()];
        }
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('toernooi_id')
            ->add('deelnemerA')
            ->add('inschrijving_id_1')
            ->add('categorie_1',
                    ChoiceType::class, ['choices' => $this->catList, 'choice_attr' => $this->catTypes] )
            ->add('cat_type_1')
            ->add('deelnemerB_1')
            ->add('aantal_1')
            ->add('inschrijving_id_2')
            ->add('categorie_2',
                    ChoiceType::class, ['choices' => $this->catList, 'choice_attr' => $this->catTypes] )
            ->add('cat_type_2')
            ->add('deelnemerB_2')
            ->add('aantal_2')
            ->add('inschrijving_id_3')
            ->add('categorie_3',
                    ChoiceType::class, ['choices' => $this->catList, 'choice_attr' => $this->catTypes] )
            ->add('cat_type_3')
            ->add('deelnemerB_3')
            ->add('aantal_3')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InschrijvingFormData::class,
            'toernooi_id' => -1,
        ]);
    }
}
