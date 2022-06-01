<?php

namespace App\Form;

use App\Entity\CartOccurence;
use App\Entity\Order;
use App\Form\EventSubscriber\OrphanRemovalSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class CustomOrderFormType extends AbstractType
{
    private Security $security;
    private OrphanRemovalSubscriber $orphanRemovalSubscriber;

    public function __construct(Security $security, OrphanRemovalSubscriber $orphanRemovalSubscriber)
    {
        $this->security = $security;
        $this->orphanRemovalSubscriber = $orphanRemovalSubscriber;

        $this->orphanRemovalSubscriber->setCollectionGetters([
            ['getterForCollection' => 'getCartOccurences', 'getterForParent' => 'getOrder']
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cartOccurences', CollectionType::class, [
                'entry_type' => CustomCartOccurenceFormType::class,
                'by_reference' => false,
                'required' => false,
                'error_bubbling' => false,
                'allow_add' => true,
                'allow_delete' => $this->security->isGranted('order_delete_custom'),
                'label' => 'Produkty',
                'delete_empty' => function (CartOccurence $cartOccurence = null) {
                    return $cartOccurence === null || $cartOccurence->isMarkedForRemoval();
                },
                'attr' => [
                    'class' => 'cartOccurences',
                    'data-reload-select' => true,
                ],
            ])
            ->add('addItem', ButtonType::class, [
                'attr' => [
                    'class' => 'btn-medium grey left js-add-item-link',
                    'data-collection-holder-class' => 'cartOccurences',
                ],
                'label' => 'Přidat produkt',
            ])
            ->addEventSubscriber($this->orphanRemovalSubscriber)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'form_custom_order',
            'validation_groups' => ['Default', 'onDemandCreation'],
        ]);
    }
}