<?php

namespace App\Form;

use App\Entity\ProductInformation;
use App\Entity\ProductInformationGroup;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductInformationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('productInformationGroup', EntityType::class, [
                'class' => ProductInformationGroup::class,
                'choice_label' => 'name',
                'label' => 'Skupina produktových informací',
            ])
            ->add('value', TextType::class, [
                'label' => 'Hodnota',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductInformation::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'form_product_info',
        ]);
    }
}