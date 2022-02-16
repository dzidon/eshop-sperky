<?php

namespace App\Form;

use App\Entity\ProductInformation;
use App\Entity\ProductInformationGroup;
use App\Form\Type\AutoCompleteTextType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductInformationNewFormType extends AbstractType
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('groupName', AutoCompleteTextType::class, [
                'mapped' => false,
                'data_autocomplete' => $this->entityManager->getRepository(ProductInformationGroup::class)->getArrayOfNames(),
                'label' => 'Název skupiny produktových informací',
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
            'csrf_token_id'   => 'form_product_info_new',
        ]);
    }
}