<?php

namespace App\Form\FormType\Admin;

use App\Entity\CartOccurence;
use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomCartOccurenceFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Název',
            ])
            ->add('optionsString', TextType::class, [
                'required' => false,
                'label' => 'Popis produktových voleb',
            ])
            ->add('quantity', IntegerType::class, [
                'attr' => [
                    'min' => 1
                ],
                'invalid_message' => 'Do počtu kusů musíte zadat celé číslo.',
                'label' => 'Ks',
            ])
            ->add('priceWithoutVat', NumberType::class, [
                'invalid_message' => 'Musíte zadat číselnou hodnotu.',
                'label' => 'Cena bez DPH v Kč',
            ])
            ->add('vat', ChoiceType::class, [
                'choices' => Product::VAT_NAMES,
                'label' => 'DPH',
            ])
            ->add('markedForRemoval', CheckboxType::class, [
                'label' => 'Smazat'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CartOccurence::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'form_custom_cartoccurence',
            'validation_groups' => ['onDemandCreation'],
        ]);
    }
}