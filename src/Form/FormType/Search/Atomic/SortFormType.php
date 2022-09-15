<?php

namespace App\Form\FormType\Search\Atomic;

use App\Entity\Detached\Search\Atomic\Sort;
use App\Form\EventSubscriber\SearchSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SortFormType extends AbstractType
{
    private SearchSubscriber $searchSubscriber;

    public function __construct(SearchSubscriber $searchSubscriber)
    {
        $this->searchSubscriber = $searchSubscriber;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event)
            {
                /** @var Sort $data */
                $data = $event->getData();

                $form = $event->getForm();
                $form->add('sortBy', ChoiceType::class, [
                    'choices' => $data->getAllSortData(),
                    'invalid_message' => 'Zvolte platný atribut řazení.',
                    'label' => $data->getLabel(),
                ]);
            })
            ->addEventSubscriber($this->searchSubscriber)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'         => Sort::class,
            'csrf_protection'    => false,
            'method'             => 'GET',
            'allow_extra_fields' => true,
        ]);
    }
}