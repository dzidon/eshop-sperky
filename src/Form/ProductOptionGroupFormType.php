<?php

namespace App\Form;

use App\Entity\ProductOption;
use App\Entity\ProductOptionGroup;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class ProductOptionGroupFormType extends AbstractType
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'attr' => ['autofocus' => 'autofocus'],
                'label' => 'Název skupiny voleb',
            ])
            ->add('options', CollectionType::class, [
                'entry_type' => ProductOptionFormType::class,
                'by_reference' => false,
                'required' => false,
                'error_bubbling' => false,
                'allow_add' => true,
                'allow_delete' => $this->security->isGranted('product_option_delete'),
                'label' => 'Volby',
                'delete_empty' => function (ProductOption $option = null) {
                    return $option === null || $option->getName() === null;
                },
                'attr' => [
                    'class' => 'options',
                ],
            ])
            ->add('addItem', ButtonType::class, [
                'attr' => [
                    'class' => 'btn-medium grey left js-add-item-link',
                    'data-collection-holder-class' => 'options',
                ],
                'label' => 'Přidat volbu',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductOptionGroup::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'form_product_option_group',
        ]);
    }
}