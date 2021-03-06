<?php

namespace App\Form;

use App\Entity\ProductInformation;
use App\Entity\ProductInformationGroup;
use App\Form\DataTransformer\ProductInformationGroupToNameTransformer;
use App\Form\Type\AutoCompleteTextType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

class ProductInformationNewFormType extends AbstractType
{
    private EntityManagerInterface $entityManager;
    private ProductInformationGroupToNameTransformer $informationGroupToNameTransformer;

    public function __construct(EntityManagerInterface $entityManager, ProductInformationGroupToNameTransformer $informationGroupToNameTransformer)
    {
        $this->entityManager = $entityManager;
        $this->informationGroupToNameTransformer = $informationGroupToNameTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('productInformationGroup', AutoCompleteTextType::class, [
                'data_autocomplete' => $this->entityManager->getRepository(ProductInformationGroup::class)->getArrayOfNames(),
                'constraints' => [
                    new Valid(),
                ],
                'label' => 'Název skupiny produktových informací',
            ])
            ->add('value', TextType::class, [
                'label' => 'Hodnota',
            ])
        ;

        $builder
            ->get('productInformationGroup')
            ->addModelTransformer($this->informationGroupToNameTransformer)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductInformation::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'form_product_info_new',
        ]);
    }
}