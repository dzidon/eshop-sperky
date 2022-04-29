<?php

namespace App\Form;

use App\Entity\Detached\Search\SearchAndSort;
use App\Form\EventSubscriber\SearchSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchTextAndSortFormType extends AbstractType
{
    private SearchSubscriber $searchSubscriber;

    public function __construct(SearchSubscriber $searchSubscriber)
    {
        $this->searchSubscriber = $searchSubscriber;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var SearchAndSort $data */
        $searchData = $builder->getForm()->getData();

        $builder
            ->add('searchPhrase', TextType::class, [
                'help' => $searchData->getSearchHelp(),
                'required' => false,
                'label' => 'Hledat',
            ])
            ->add('sortBy', ChoiceType::class, [
                'choices' => $searchData->getAllSortData(),
                'invalid_message' => 'Zvolte platný atribut řazení.',
                'label' => 'Řazení',
            ])
            ->addEventSubscriber($this->searchSubscriber)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'         => SearchAndSort::class,
            'csrf_protection'    => false,
            'method'             => 'GET',
            'allow_extra_fields' => true,
        ]);
    }
}