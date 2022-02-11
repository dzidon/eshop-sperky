<?php

namespace App\Form;

use App\Entity\ProductCategory;
use App\Entity\ProductCategoryGroup;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class ProductCategoryGroupFormType extends AbstractType
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
                'label' => 'Název skupiny kategorií',
            ])
            ->add('categories', CollectionType::class, [
                'entry_type' => ProductCategoryFormType::class,
                'by_reference' => false,
                'required' => false,
                'allow_add' => true,
                'allow_delete' => $this->security->isGranted('product_category_delete'),
                'label' => 'Kategorie',
                'delete_empty' => function (ProductCategory $category = null) {
                    return $category === null || $category->getName() === null;
                },
                'attr' => [
                    'class' => 'categories',
                ],
            ])
            ->add('addItem', ButtonType::class, [
                'attr' => [
                    'class' => 'btn-medium grey left js-add-item-link',
                    'data-collection-holder-class' => 'categories',
                ],
                'label' => 'Přidat kategorii',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductCategoryGroup::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'form_product_category_group',
        ]);
    }
}