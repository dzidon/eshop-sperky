<?php

namespace App\Form;

use App\Entity\ProductCategoryGroup;
use App\Form\DataTransformer\ProductCategoryGroupToNameTransformer;
use App\Form\DataTransformer\ProductCategoryToNameTransformer;
use App\Form\Type\AutoCompleteTextType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

class ProductCategoryNewFormType extends AbstractType
{
    private EntityManagerInterface $entityManager;
    private ProductCategoryGroupToNameTransformer $categoryGroupToNameTransformer;
    private ProductCategoryToNameTransformer $categoryToNameTransformer;

    public function __construct(EntityManagerInterface $entityManager, ProductCategoryGroupToNameTransformer $categoryGroupToNameTransformer, ProductCategoryToNameTransformer $categoryToNameTransformer)
    {
        $this->entityManager = $entityManager;
        $this->categoryGroupToNameTransformer = $categoryGroupToNameTransformer;
        $this->categoryToNameTransformer = $categoryToNameTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('categoryGroup', AutoCompleteTextType::class, [
                'data_autocomplete' => $this->entityManager->getRepository(ProductCategoryGroup::class)->getArrayOfNames(),
                'constraints' => [
                    new Valid(),
                ],
                'label' => 'Název skupiny kategorií',
            ])
            ->add('category', TextType::class, [
                'constraints' => [
                    new Valid(),
                ],
                'label' => 'Název kategorie',
            ])
        ;

        $builder
            ->get('categoryGroup')
            ->addModelTransformer($this->categoryGroupToNameTransformer)
        ;

        $builder
            ->get('category')
            ->addModelTransformer($this->categoryToNameTransformer)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'form_product_category_new',
        ]);
    }
}