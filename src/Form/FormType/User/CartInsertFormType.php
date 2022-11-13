<?php

namespace App\Form\FormType\User;

use App\Entity\Detached\CartInsert;
use App\Entity\Product;
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
        /** @var CartInsert $cartInsert */
        $cartInsert = $builder->getForm()->getData();

        /** @var Product|null $product */
        $product = $cartInsert->getProduct();

        $productId = null;
        if ($product !== null)
        {
            $productId = $product->getId();
        }

        $builder
            ->add('productId', HiddenType::class, [
                'mapped' => false,
                'data' => $productId,
                'error_bubbling' => true,
            ])
            ->add('quantity', IntegerType::class, [
                'attr' => [
                    'min' => 1
                ],
                'error_bubbling' => true,
                'label' => 'Ks',
            ])
            ->add('optionGroups', CartInsertOptionGroupsFormType::class, [
                'empty_data' => [],
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