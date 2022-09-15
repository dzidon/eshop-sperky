<?php

namespace App\Form\FormType\Search\Composition;

use App\Entity\Detached\Search\Composition\PhraseSortDropdown;
use App\Form\EventSubscriber\SearchSubscriber;
use App\Form\FormType\Search\Atomic\DropdownFormType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PhraseSortDropdownFormType extends AbstractType
{
    private SearchSubscriber $searchSubscriber;

    public function __construct(SearchSubscriber $searchSubscriber)
    {
        $this->searchSubscriber = $searchSubscriber;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('phraseSort', PhraseSortFormType::class)
            ->add('dropdown', DropdownFormType::class)
            ->addEventSubscriber($this->searchSubscriber)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'         => PhraseSortDropdown::class,
            'csrf_protection'    => false,
            'method'             => 'GET',
            'allow_extra_fields' => true,
        ]);
    }
}