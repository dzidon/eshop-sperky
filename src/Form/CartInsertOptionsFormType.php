<?php

namespace App\Form;

use App\Entity\Detached\CartInsert;
use App\Entity\ProductOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;

class CartInsertOptionsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            $formParent = $form->getParent();

            /** @var CartInsert $cartInsertData */
            $cartInsertData = $formParent->getData();
            $productOptions = $cartInsertData->getProduct()->getOptions();

            if (!$productOptions->isEmpty())
            {
                foreach ($productOptions as $option)
                {
                    if ($option->isConfigured())
                    {
                        // Dropdown
                        if ($option->getType() === ProductOption::TYPE_DROPDOWN)
                        {
                            $items = [];
                            $emptyData = null;
                            foreach ($option->getParameters() as $parameter)
                            {
                                if($emptyData === null)
                                {
                                    $emptyData = $parameter->getValue();
                                }
                                $items[$parameter->getValue()] = $parameter->getValue();
                            }

                            $form->add(sprintf('%s', $option->getId()), ChoiceType::class, [
                                'invalid_message' => sprintf('Do pole "%s" jste zadali neplatnou hodnotu. Zkuste prosím aktualizovat stránku.', $option->getName()),
                                'choices' => $items,
                                'empty_data' => $emptyData,
                                'label' => $option->getName(),
                                'error_bubbling' => true,
                            ]);
                        }
                        // Číslo
                        else if ($option->getType() === ProductOption::TYPE_NUMBER)
                        {
                            $form->add(sprintf('%s', $option->getId()), NumberType::class, [
                                'invalid_message' => sprintf('Do pole "%s" jste zadali neplatnou hodnotu.', $option->getName()),
                                'html5' => true,
                                'constraints' => [
                                    new GreaterThanOrEqual([
                                        'value' => $option->getParameterValue('min'),
                                        'message' => sprintf('Hodnota pole "%s" musí být větší nebo rovna hodnotě {{ compared_value }}.', $option->getName()),
                                    ]),
                                    new LessThanOrEqual([
                                        'value' => $option->getParameterValue('max'),
                                        'message' => sprintf('Hodnota pole "%s" musí být menší nebo rovna hodnotě {{ compared_value }}.', $option->getName()),
                                    ])
                                ],
                                'attr' => [
                                    'min' => $option->getParameterValue('min'),
                                    'max' => $option->getParameterValue('max'),
                                    'step' => $option->getParameterValue('step'),
                                    'value' => $option->getParameterValue('default'),
                                ],
                                'empty_data' => $option->getParameterValue('default'),
                                'label' => $option->getName(),
                                'error_bubbling' => true,
                            ]);
                        }
                    }
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'form_cart_insert_options',
        ]);
    }
}