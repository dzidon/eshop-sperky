<?php

namespace App\Form;

use App\Entity\ProductImage;
use App\Form\EventSubscriber\EntityMarkForRemovalSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class ProductImageFormType extends AbstractType
{
    private EntityMarkForRemovalSubscriber $entityMarkForRemovalSubscriber;

    public function __construct(EntityMarkForRemovalSubscriber $entityMarkForRemovalSubscriber)
    {
        $this->entityMarkForRemovalSubscriber = $entityMarkForRemovalSubscriber;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('file', VichImageType::class, [
                'required' => false,
                'allow_delete' => false,
                'download_uri' => false,
                'image_uri' => true,
                'asset_helper' => true,
                'label' => false,
            ])
            ->add('priority', NumberType::class, [
                'required' => false,
                'invalid_message' => 'Musíte zadat číselnou hodnotu.',
                'label' => 'Priorita',
            ])
            ->addEventSubscriber($this->entityMarkForRemovalSubscriber)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductImage::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'form_product_image',
        ]);
    }
}