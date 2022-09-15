<?php

namespace App\Form\FormType\Search\Composition;

use App\Entity\Detached\Search\Composition\PhraseSort;
use App\Form\EventSubscriber\SearchSubscriber;
use App\Form\FormType\Search\Atomic\PhraseFormType;
use App\Form\FormType\Search\Atomic\SortFormType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PhraseSortFormType extends AbstractType
{
    private SearchSubscriber $searchSubscriber;

    public function __construct(SearchSubscriber $searchSubscriber)
    {
        $this->searchSubscriber = $searchSubscriber;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('phrase', PhraseFormType::class)
            ->add('sort', SortFormType::class)
            ->addEventSubscriber($this->searchSubscriber)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'         => PhraseSort::class,
            'csrf_protection'    => false,
            'method'             => 'GET',
            'allow_extra_fields' => true,
        ]);
    }
}