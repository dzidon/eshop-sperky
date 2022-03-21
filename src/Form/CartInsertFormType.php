<?php

namespace App\Form;

use App\Entity\Detached\CartInsert;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CartInsertFormType extends AbstractType
{
    private UrlGeneratorInterface $router;

    public function __construct(UrlGeneratorInterface $router)
    {
        $this->router = $router;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('quantity', IntegerType::class, [
                'attr' => [
                    'min' => 1
                ],
                'invalid_message' => 'Do počtu kusů musíte zadat celé číslo.',
                'label' => 'Ks',
                'error_bubbling' => true,
            ])
            ->add('productId', HiddenType::class, [
                'error_bubbling' => true,
            ])
            ->add('options', CartInsertOptionsFormType::class, [
                'error_bubbling' => true,
                'label' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CartInsert::class,
            'action' => $this->router->generate('cart_insert'),
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'form_cart_insert',
            'attr' => [
                'id' => 'form-cart-insert'
            ],
        ]);
    }
}