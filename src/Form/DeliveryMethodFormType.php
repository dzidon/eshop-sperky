<?php

namespace App\Form;

use App\Entity\DeliveryMethod;
use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class DeliveryMethodFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'attr' => ['autofocus' => 'autofocus'],
                'label' => 'Název',
            ])
            ->add('imageFile', VichImageType::class, [
                'required' => false,
                'allow_delete' => true,
                'download_uri' => false,
                'image_uri' => true,
                'asset_helper' => true,
                'label' => 'Obrázek',
            ])
            ->add('priceWithoutVat', NumberType::class, [
                'attr' => [
                    'class' => 'js-input-price-without-vat',
                ],
                'invalid_message' => 'Musíte zadat číselnou hodnotu.',
                'label' => 'Cena bez DPH v Kč',
            ])
            ->add('vat', ChoiceType::class, [
                'attr' => [
                    'class' => 'js-input-vat',
                ],
                'choices' => Product::VAT_NAMES,
                'label' => 'DPH',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DeliveryMethod::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'form_delivery_method',
        ]);
    }
}