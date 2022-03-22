<?php

namespace App\Form;

use App\Entity\Detached\CartInsert;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CartInsertOptionGroupsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            $formParent = $form->getParent();

            /** @var CartInsert $cartInsertData */
            $cartInsertData = $formParent->getData();
            $productOptionGroups = $cartInsertData->getProduct()->getOptionGroups();

            foreach ($productOptionGroups as $productOptionGroup)
            {
                $options = $productOptionGroup->getOptions();
                if(!$options->isEmpty())
                {
                    $choices = $options->toArray();
                    $form->add(sprintf('%s', $productOptionGroup->getId()), ChoiceType::class, [
                        'invalid_message' => sprintf('Do pole "%s" jste zadali neplatnou hodnotu. Zkuste prosím aktualizovat stránku.', $productOptionGroup->getName()),
                        'choices' => $choices,
                        'choice_label' => 'name',
                        'choice_value' => 'id',
                        'empty_data' => (string) $choices[array_key_first($choices)]->getId(),
                        'label' => $productOptionGroup->getName(),
                        'error_bubbling' => true,
                    ]);
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'form_cart_insert_option_group',
        ]);
    }
}