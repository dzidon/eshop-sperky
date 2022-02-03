<?php

namespace App\Form\EventSubscriber;

use App\Entity\ProductOption;
use App\Entity\ProductOptionParameter;
use App\Form\ProductOptionDropdownFormType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ProductOptionParametersSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [FormEvents::PRE_SET_DATA => 'preSetData'];
    }

    public function preSetData(FormEvent $event): void
    {
        $option = $event->getData();
        $form = $event->getForm();

        if ($option->getType() === ProductOption::TYPE_DROPDOWN)
        {
            $form
                ->add('parameters', CollectionType::class, [
                    'entry_type' => ProductOptionDropdownFormType::class,
                    'by_reference' => false,
                    'required' => false,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'label' => 'Obsah skládacího menu',
                    'delete_empty' => function (ProductOptionParameter $parameter = null) {
                        return $parameter === null || $parameter->getValue() === null;
                    },
                    'attr' => [
                        'class' => 'parameters',
                    ],
                ])
                ->add('addItem', ButtonType::class, [
                    'attr' => [
                        'class' => 'btn-medium grey left add_item_link',
                        'data-collection-holder-class' => 'parameters',
                    ],
                    'label' => 'Přidat hodnotu',
                ])
            ;
        }
        else if ($option->getType() === ProductOption::TYPE_NUMBER)
        {
            $form
                ->add('min', TextType::class, [
                    'constraints' => [
                        new NotBlank(),
                        new Type('numeric', 'Musíte zadat číselnou hodnotu.'),
                        new Callback([
                            'callback' => function ($value, ExecutionContextInterface $context) use ($form)
                            {
                                if($value >= $form->get('max')->getData())
                                {
                                    $context->buildViolation('Minimální číslo musí být menší než maximální číslo.')
                                        ->atPath('min')
                                        ->addViolation();
                                }
                            }
                        ]),
                    ],
                    'mapped' => false,
                    'data' => $option->getParameterValue('min'),
                    'label' => 'Minimální povolené číslo',
                ])
                ->add('max', TextType::class, [
                    'constraints' => [
                        new NotBlank(),
                        new Type('numeric', 'Musíte zadat číselnou hodnotu.'),
                    ],
                    'mapped' => false,
                    'data' => $option->getParameterValue('max'),
                    'label' => 'Maximální povolené číslo',
                ])
                ->add('default', TextType::class, [
                    'constraints' => [
                        new NotBlank(),
                        new Type('numeric', 'Musíte zadat číselnou hodnotu.'),
                        new Callback([
                            'callback' => function ($value, ExecutionContextInterface $context) use ($form)
                            {
                                if($value < $form->get('min')->getData() || $value > $form->get('max')->getData())
                                {
                                    $context->buildViolation('Výchozí číslo musí být mezi minimálním a maximálním číslem.')
                                        ->atPath('default')
                                        ->addViolation();
                                }
                            }
                        ]),
                    ],
                    'mapped' => false,
                    'data' => $option->getParameterValue('default'),
                    'label' => 'Výchozí číslo',
                ])
                ->add('step', TextType::class, [
                    'constraints' => [
                        new NotBlank(),
                        new Type('numeric', 'Musíte zadat číselnou hodnotu.'),
                        new GreaterThan(0),
                    ],
                    'mapped' => false,
                    'data' => $option->getParameterValue('step'),
                    'help' => 'O kolik se má změnit číslo, když uživatel klikne na šipku pro zvětšení/zmenšení?',
                    'label' => 'Číselná změna',
                ])
            ;
        }
    }
}