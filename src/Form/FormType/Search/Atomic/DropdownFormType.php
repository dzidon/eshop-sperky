<?php

namespace App\Form\FormType\Search\Atomic;

use App\Entity\Detached\Search\Atomic\Dropdown;
use App\Form\EventSubscriber\SearchSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DropdownFormType extends AbstractType
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
                /** @var Dropdown $data */
                $data = $event->getData();

                $required = true;
                $placeholder = false;
                if ($data->getPlaceholder() !== null)
                {
                    $required = false;
                    $placeholder = $data->getPlaceholder();
                }

                $form = $event->getForm();
                $form->add('choice', ChoiceType::class, [
                    'required' => $required,
                    'placeholder' => $placeholder,
                    'choices' => $data->getChoices(),
                    'invalid_message' => 'Zvolte platnou volbu.',
                    'label' => $data->getLabel(),
                ]);
            })
            ->addEventSubscriber($this->searchSubscriber)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'         => Dropdown::class,
            'csrf_protection'    => false,
            'method'             => 'GET',
            'allow_extra_fields' => true,
        ]);
    }
}