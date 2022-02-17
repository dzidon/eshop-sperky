<?php

namespace App\Form;

use App\Entity\ProductSection;
use App\Form\EventSubscriber\SlugSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductSectionFormType extends AbstractType
{
    private SlugSubscriber $slugSubscriber;

    public function __construct(SlugSubscriber $slugSubscriber)
    {
        $this->slugSubscriber = $slugSubscriber->setGettersForAutoGenerate(['getName']);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'attr' => ['autofocus' => 'autofocus'],
                'help' => 'Pro uživatele přívětivý název, např. "Kvalitní náušnice".',
                'label' => 'Název',
            ])
            ->add('slug', TextType::class, [
                'help' => 'Pro odkazy přívětivý název, např. "kvalitni-nausnice". Pole můžete nechat prázdné pro automatické vygenerování z předešlého názvu. Všechny nebezpečné znaky tohoto názvu jsou převedeny na bezpečné.',
                'required' => false,
                'label' => 'Název v odkazu',
            ])
            ->add('availableSince', DateTimeType::class, [
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime',
                'label' => 'Zpřístupnit pro uživatele od',
            ])
            ->add('isHidden', CheckboxType::class, [
                'required' => false,
                'label' => 'Manuálně skrýt pro uživatele',
            ])
            ->addEventSubscriber($this->slugSubscriber)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductSection::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'form_product_section',
        ]);
    }
}