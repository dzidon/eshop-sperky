<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Entity\ProductInformation;
use App\Entity\ProductOption;
use App\Entity\ProductSection;
use App\Form\EventSubscriber\EntityCollectionAdditionSubscriber;
use App\Form\EventSubscriber\EntityCollectionRemovalSubscriber;
use App\Form\EventSubscriber\ProductInformationSubscriber;
use App\Form\EventSubscriber\SlugSubscriber;
use App\Repository\ProductCategoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductFormType extends AbstractType
{
    private ProductInformationSubscriber $productInformationSubscriber;
    private SlugSubscriber $slugSubscriber;
    private EntityCollectionRemovalSubscriber $collectionRemovalSubscriber;
    private EntityCollectionAdditionSubscriber $collectionAdditionSubscriber;

    public function __construct(ProductInformationSubscriber $productInformationSubscriber, SlugSubscriber $slugSubscriber, EntityCollectionRemovalSubscriber $collectionRemovalSubscriber, EntityCollectionAdditionSubscriber $collectionAdditionSubscriber)
    {
        $this->productInformationSubscriber = $productInformationSubscriber;
        $this->collectionRemovalSubscriber = $collectionRemovalSubscriber->setCollectionGetters(['getInfo']);
        $this->collectionAdditionSubscriber = $collectionAdditionSubscriber->addFieldNameAndAdder('infoNew', 'addInfo');
        $this->slugSubscriber = $slugSubscriber
                                    ->setGettersForAutoGenerate(['getName'])
                                    ->setExtraDataForAutoGenerate([date("HisdmY")]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'attr' => ['autofocus' => 'autofocus'],
                'help' => 'Pro uživatele přívětivý název, např. "Náhrdelník Mateřídouška".',
                'label' => 'Název',
            ])
            ->add('slug', TextType::class, [
                'help' => 'Pro odkazy přívětivý název, např. "nahrdelnik-materidouska". Pole můžete nechat prázdné pro automatické vygenerování z předešlého názvu. Všechny nebezpečné znaky tohoto názvu jsou převedeny na bezpečné.',
                'required' => false,
                'label' => 'Název v odkazu',
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Popis',
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
            ->add('priceWithoutVat', TextType::class, [
                'attr' => [
                    'class' => 'js-input-price-without-vat',
                ],
                'label' => 'Cena bez DPH v Kč',
            ])
            ->add('vat', ChoiceType::class, [
                'attr' => [
                    'class' => 'js-input-vat',
                ],
                'choices' => Product::VAT_NAMES,
                'label' => 'DPH',
            ])
            ->add('section', EntityType::class, [
                'class' => ProductSection::class,
                'required' => false,
                'placeholder' => '-- nezařazeno --',
                'choice_label' => 'name',
                'label' => 'Sekce',
            ])
            ->add('categories', EntityType::class, [
                'class' => ProductCategory::class,
                'choice_label' => 'name',
                'query_builder' => function (ProductCategoryRepository $er) {
                    return $er->qbFindAllAndFetchGroups();
                },
                'group_by' => function(ProductCategory $category) {
                    return $category->getProductCategoryGroup()->getName();
                },
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'label' => false,
            ])
            ->add('options', EntityType::class, [
                'class' => ProductOption::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'label' => false,
            ])
            ->add('info', CollectionType::class, [
                'entry_type' => ProductInformationFormType::class,
                'by_reference' => false,
                'required' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => function (ProductInformation $information = null) {
                    return $information === null || $information->getValue() === null;
                },
                'label' => 'Výběr z již existujících skupin informací',
                'attr' => [
                    'class' => 'info',
                    'data-reload-select' => true,
                ],
            ])
            ->add('addItem', ButtonType::class, [
                'attr' => [
                    'class' => 'btn-medium grey left js-add-item-link',
                    'data-collection-holder-class' => 'info',
                ],
                'label' => 'Přidat informaci',
            ])
            ->addEventSubscriber($this->slugSubscriber)
            ->addEventSubscriber($this->productInformationSubscriber)
            ->addEventSubscriber($this->collectionRemovalSubscriber)
            ->addEventSubscriber($this->collectionAdditionSubscriber)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'form_product',
        ]);
    }
}