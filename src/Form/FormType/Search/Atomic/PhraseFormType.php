<?php

namespace App\Form\FormType\Search\Atomic;

use App\Entity\Detached\Search\Atomic\Phrase;
use App\Form\EventSubscriber\SearchSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PhraseFormType extends AbstractType
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
                /** @var Phrase $data */
                $data = $event->getData();

                $form = $event->getForm();
                $form->add('text', TextType::class, [
                    'help' => $data->getSearchHelp(),
                    'required' => false,
                    'label' => $data->getLabel(),
                    'attr' => [
                        'autofocus' => 'autofocus',
                    ],
                ]);
            })
            ->addEventSubscriber($this->searchSubscriber)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'         => Phrase::class,
            'csrf_protection'    => false,
            'method'             => 'GET',
            'allow_extra_fields' => true,
        ]);
    }
}