<?php

namespace App\Form;

use App\Entity\Order;
use App\Entity\Detached\Search\SearchOrder;
use App\Form\EventSubscriber\SearchSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderSearchFormType extends AbstractType
{
    private SearchSubscriber $searchSubscriber;

    public function __construct(SearchSubscriber $searchSubscriber)
    {
        $this->searchSubscriber = $searchSubscriber;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var SearchOrder $data */
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
            ->add('lifecycle', ChoiceType::class, [
                'required' => false,
                'choices' => array_flip(Order::LIFECYCLE_CHAPTERS),
                'invalid_message' => 'Zvolte platný stav objednávky.',
                'placeholder' => '-- všechny --',
                'label' => 'Stav objednávky',
            ])
            ->addEventSubscriber($this->searchSubscriber)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'         => SearchOrder::class,
            'method'             => 'GET',
            'csrf_protection'    => false,
            'allow_extra_fields' => true,
        ]);
    }
}