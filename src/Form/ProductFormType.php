<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Entity\ProductImage;
use App\Entity\ProductInformation;
use App\Entity\ProductInformationGroup;
use App\Entity\ProductOptionGroup;
use App\Entity\ProductSection;
use App\Form\EventSubscriber\ProductCategorySubscriber;
use App\Form\EventSubscriber\ProductInformationSubscriber;
use App\Form\EventSubscriber\SlugGeneratorSubscriber\SlugGeneratorWithTimeSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductFormType extends AbstractType
{
    private ProductInformationSubscriber $productInformationSubscriber;
    private ProductCategorySubscriber $productCategorySubscriber;
    private SlugGeneratorWithTimeSubscriber $slugGeneratorSubscriber;
    private EntityManagerInterface $entityManager;

    public function __construct(ProductInformationSubscriber $productInformationSubscriber, ProductCategorySubscriber $productCategorySubscriber, SlugGeneratorWithTimeSubscriber $slugGeneratorSubscriber, EntityManagerInterface $entityManager)
    {
        $this->productInformationSubscriber = $productInformationSubscriber;
        $this->productCategorySubscriber = $productCategorySubscriber;
        $this->slugGeneratorSubscriber = $slugGeneratorSubscriber;
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // existuj??c?? kategorie
        $categories = $this->entityManager->getRepository(ProductCategory::class)->findAllAndFetchGroups();
        if ($categories !== null && count($categories) > 0)
        {
            $builder
                ->add('categories', EntityType::class, [
                    'class' => ProductCategory::class,
                    'choice_label' => 'name',
                    'choices' => $categories,
                    'group_by' => function(ProductCategory $category) {
                        return $category->getProductCategoryGroup()->getName();
                    },
                    'multiple' => true,
                    'expanded' => true,
                    'required' => false,
                    'label' => false,
                ])
            ;
        }

        // existuj??c?? skupiny informac??
        $infoGroups = $this->entityManager->getRepository(ProductInformationGroup::class)->findAll();
        if ($infoGroups !== null && count($infoGroups) > 0)
        {
            $builder
                ->add('info', CollectionType::class, [
                    'entry_type' => ProductInformationFormType::class,
                    'by_reference' => false,
                    'required' => false,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'delete_empty' => function (ProductInformation $information = null) {
                        return $information === null || $information->getValue() === null;
                    },
                    'label' => false,
                    'attr' => [
                        'class' => 'info',
                        'data-reload-select' => true,
                    ],
                    'entry_options' => [
                        'information_groups' => $infoGroups,
                    ],
                ])
                ->add('addItemInfo', ButtonType::class, [
                    'attr' => [
                        'class' => 'btn-medium grey left js-add-item-link',
                        'data-collection-holder-class' => 'info',
                    ],
                    'label' => 'P??idat informaci',
                ])
            ;
        }

        // produktov?? volby
        $optionGroups = $this->entityManager->getRepository(ProductOptionGroup::class)->findAll();
        if ($optionGroups !== null && count($optionGroups))
        {
            $builder
                ->add('optionGroups', EntityType::class, [
                    'class' => ProductOptionGroup::class,
                    'choices' => $optionGroups,
                    'choice_label' => 'name',
                    'multiple' => true,
                    'expanded' => true,
                    'required' => false,
                    'label' => false,
                ])
            ;
        }

        // zbytek
        $builder
            ->add('name', TextType::class, [
                'attr' => ['autofocus' => 'autofocus'],
                'help' => 'Pro u??ivatele p????v??tiv?? n??zev, nap??. "N??hrdeln??k Mate????dou??ka".',
                'label' => 'N??zev',
            ])
            ->add('slug', TextType::class, [
                'help' => 'Pro odkazy p????v??tiv?? n??zev, nap??. "nahrdelnik-materidouska". Pole m????ete nechat pr??zdn?? pro automatick?? vygenerov??n?? z p??ede??l??ho n??zvu. V??echny nebezpe??n?? znaky tohoto n??zvu jsou p??evedeny na bezpe??n??.',
                'required' => false,
                'label' => 'N??zev v odkazu',
            ])
            ->add('descriptionShort', TextareaType::class, [
                'attr' => [
                    'data-length' => 250,
                ],
                'required' => false,
                'label' => 'Kr??tk?? popis',
            ])
            ->add('description', TextareaType::class, [
                'attr' => [
                    'data-length' => 4096,
                ],
                'required' => false,
                'label' => 'Dlouh?? popis',
            ])
            ->add('inventory', IntegerType::class, [
                'attr' => [
                    'min' => 0
                ],
                'label' => 'Po??et kus?? na sklad??',
            ])
            ->add('availableSince', DateTimeType::class, [
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime',
                'label' => 'Zp????stupnit pro u??ivatele od',
            ])
            ->add('hideWhenSoldOut', CheckboxType::class, [
                'required' => false,
                'label' => 'Skr??t pro u??ivatele po vyprod??n??',
            ])
            ->add('isHidden', CheckboxType::class, [
                'required' => false,
                'label' => 'Manu??ln?? skr??t pro u??ivatele',
            ])
            ->add('priceWithoutVat', NumberType::class, [
                'attr' => [
                    'class' => 'js-input-price-without-vat',
                ],
                'invalid_message' => 'Mus??te zadat ????selnou hodnotu.',
                'label' => 'Cena bez DPH v K??',
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
                'placeholder' => '-- neza??azeno --',
                'choice_label' => 'name',
                'label' => 'Sekce',
            ])
            ->add('images', CollectionType::class, [
                'entry_type' => ProductImageFormType::class,
                'by_reference' => false,
                'required' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => function (ProductImage $image = null) {
                    return $image === null || $image->isMarkedForRemoval();
                },
                'label' => false,
                'attr' => [
                    'class' => 'images',
                ],
            ])
            ->add('addItemImage', ButtonType::class, [
                'attr' => [
                    'class' => 'btn-medium grey left js-add-item-link',
                    'data-collection-holder-class' => 'images',
                ],
                'label' => 'P??idat obr??zek',
            ])
            ->addEventSubscriber($this->slugGeneratorSubscriber)
            ->addEventSubscriber($this->productInformationSubscriber)
            ->addEventSubscriber($this->productCategorySubscriber)
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