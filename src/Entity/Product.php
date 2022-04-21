<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use App\Service\SortingService;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Validation\Compound as AssertCompound;

/**
 * @ORM\Entity(repositoryClass=ProductRepository::class)
 * @ORM\Table(indexes={@ORM\Index(name="search_idx", columns={"name", "created", "price_with_vat", "inventory"})})
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(fields={"slug"}, message="Už existuje produktová sekce s tímto názvem pro odkaz.")
 */
class Product
{
    public const VAT_NONE = 0.0;
    public const VAT_BASIC = 0.21;
    public const VAT_LOWER_1 = 0.15;
    public const VAT_LOWER_2 = 0.10;

    public const VAT_VALUES = [
        self::VAT_NONE, self::VAT_BASIC, self::VAT_LOWER_1, self::VAT_LOWER_2
    ];

    public const VAT_NAMES = [
        'Žádná (0%)' => self::VAT_NONE,
        'Základní sazba (21%)' => self::VAT_BASIC,
        'První snížená sazba (15%)' => self::VAT_LOWER_1,
        'Druhá snížená sazba (10%)' => self::VAT_LOWER_2,
    ];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Assert\Length(max=255, maxMessage="Maximální počet znaků v názvu produktu: {{ limit }}")
     * @Assert\NotBlank
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     *
     * @Assert\Length(max=255, maxMessage="Maximální počet znaků v názvu pro odkaz: {{ limit }}")
     * @Assert\NotBlank
     */
    private $slug;

    /**
     * @ORM\Column(type="float")
     *
     * @Assert\Type("numeric", message="Musíte zadat číselnou hodnotu.")
     * @Assert\NotBlank
     */
    private $priceWithoutVat;

    /**
     * @ORM\Column(type="float")
     */
    private $priceWithVat;

    /**
     * @ORM\Column(type="float")
     *
     * @AssertCompound\VatRequirements
     */
    private $vat;

    /**
     * @ORM\Column(type="string", length=250, nullable=true)
     *
     * @Assert\Length(max=250, maxMessage="Maximální počet znaků v krátkém popisu: {{ limit }}")
     */
    private $descriptionShort;

    /**
     * @ORM\Column(type="string", length=4096, nullable=true)
     *
     * @Assert\Length(max=4096, maxMessage="Maximální počet znaků v dlouhém popisu: {{ limit }}")
     */
    private $description;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Assert\Type("bool", message="Zadávaná hodnota není platná.")
     */
    private $isHidden = false;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Assert\Type("bool", message="Zadávaná hodnota není platná.")
     */
    private $hideWhenSoldOut = false;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @Assert\Type("DateTime", message="Musíte zadat datum a čas.")
     */
    private $availableSince;

    /**
     * @ORM\Column(type="integer")
     *
     * @Assert\Type("integer", message="Musíte zadat číselnou hodnotu.")
     * @Assert\GreaterThanOrEqual(0)
     * @Assert\NotBlank
     */
    private $inventory;

    /**
     * @ORM\ManyToOne(targetEntity=ProductSection::class)
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $section;

    /**
     * @ORM\ManyToMany(targetEntity=ProductCategory::class, inversedBy="products", cascade={"persist"})
     * @ORM\JoinTable(name="_product_category")
     */
    private $categories;

    /**
     * @ORM\ManyToMany(targetEntity=ProductOptionGroup::class)
     * @ORM\JoinTable(name="_product_optiongroup")
     */
    private $optionGroups;

    /**
     * @ORM\OneToMany(targetEntity=ProductInformation::class, mappedBy="product", cascade={"persist"})
     *
     * @Assert\Valid
     */
    private $info;

    /**
     * @ORM\OneToMany(targetEntity=ProductImage::class, mappedBy="product", cascade={"persist"})
     *
     * @Assert\Valid
     */
    private $images;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $mainImageName;

    /**
     * @ORM\OneToMany(targetEntity=CartOccurence::class, mappedBy="product", cascade={"persist"})
     */
    private $cartOccurences;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updated;

    public function __construct()
    {
        $this->created = new DateTime('now');
        $this->updated = $this->created;

        $this->optionGroups = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->info = new ArrayCollection();
        $this->images = new ArrayCollection();
        $this->cartOccurences = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getPriceWithoutVat(): ?float
    {
        return $this->priceWithoutVat;
    }

    public function setPriceWithoutVat(?float $priceWithoutVat): self
    {
        $this->priceWithoutVat = $priceWithoutVat;

        return $this;
    }

    public function getPriceWithVat(): ?float
    {
        return $this->priceWithVat;
    }

    public function setPriceWithVat(float $priceWithVat): self
    {
        $this->priceWithVat = $priceWithVat;

        return $this;
    }

    /**
     * @ORM\PreFlush
     */
    public function calculatePriceWithVat(): self
    {
        $this->priceWithVat = $this->priceWithoutVat * (1 + $this->vat);

        return $this;
    }

    public function getVat(): ?float
    {
        return $this->vat;
    }

    public function setVat(float $vat): self
    {
        $this->vat = $vat;

        return $this;
    }

    public function getDescriptionShort(): ?string
    {
        return $this->descriptionShort;
    }

    public function setDescriptionShort(?string $descriptionShort): self
    {
        $this->descriptionShort = $descriptionShort;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function isHidden(): ?bool
    {
        return $this->isHidden;
    }

    public function setIsHidden(?bool $isHidden): self
    {
        $this->isHidden = $isHidden;

        return $this;
    }

    public function isHideWhenSoldOut(): ?bool
    {
        return $this->hideWhenSoldOut;
    }

    public function setHideWhenSoldOut(?bool $hideWhenSoldOut): self
    {
        $this->hideWhenSoldOut = $hideWhenSoldOut;

        return $this;
    }

    public function isVisible(): bool
    {
        if($this->isHidden
           || ($this->availableSince !== null && $this->availableSince > (new DateTime('now')))
           || ($this->hideWhenSoldOut && $this->inventory <= 0))
        {
            return false;
        }

        return true;
    }

    public function getAvailableSince(): ?DateTimeInterface
    {
        return $this->availableSince;
    }

    public function setAvailableSince(?DateTimeInterface $availableSince): self
    {
        $this->availableSince = $availableSince;

        return $this;
    }

    public function getInventory(): ?int
    {
        return $this->inventory;
    }

    public function setInventory(?int $inventory): self
    {
        $this->inventory = $inventory;

        return $this;
    }

    public function isInStock(): bool
    {
        return $this->inventory > 0;
    }

    public function getSection(): ?ProductSection
    {
        return $this->section;
    }

    public function setSection(?ProductSection $section): self
    {
        $this->section = $section;

        return $this;
    }

    /**
     * @return Collection|ProductCategory[]
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(ProductCategory $category): self
    {
        if (!$this->categories->contains($category))
        {
            $this->categories[] = $category;
            $category->addProduct($this);
        }

        return $this;
    }

    public function removeCategory(ProductCategory $category): self
    {
        if ($this->categories->contains($category))
        {
            $this->categories->removeElement($category);
            $category->removeProduct($this);
        }

        return $this;
    }

    public function getCategoryNamesGrouped(): array
    {
        $categoriesGrouped = array();
        foreach ($this->categories as $category)
        {
            $categoriesGrouped[$category->getProductCategoryGroup()->getName()][] = $category->getName();
        }

        return $categoriesGrouped;
    }

    /**
     * @return Collection|ProductOptionGroup[]
     */
    public function getOptionGroups(): Collection
    {
        return $this->optionGroups;
    }

    public function addOptionGroup(ProductOptionGroup $optionGroup): self
    {
        if (!$this->optionGroups->contains($optionGroup)) {
            $this->optionGroups[] = $optionGroup;
        }

        return $this;
    }

    public function removeOptionGroup(ProductOptionGroup $optionGroup): self
    {
        $this->optionGroups->removeElement($optionGroup);

        return $this;
    }

    /**
     * @return Collection|ProductInformation[]
     */
    public function getInfo(): Collection
    {
        return $this->info;
    }

    public function addInfo(ProductInformation $info): self
    {
        if (!$this->info->contains($info)) {
            $this->info[] = $info;
            $info->setProduct($this);
        }

        return $this;
    }

    public function removeInfo(ProductInformation $info): self
    {
        if ($this->info->removeElement($info)) {
            // set the owning side to null (unless already changed)
            if ($info->getProduct() === $this) {
                $info->setProduct(null);
            }
        }

        return $this;
    }

    public function getInfoValuesGrouped(): array
    {
        $infoGrouped = array();
        foreach ($this->info as $info)
        {
            $infoGrouped[$info->getProductInformationGroup()->getName()][] = $info->getValue();
        }

        return $infoGrouped;
    }

    /**
     * @return Collection|ProductImage[]
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(ProductImage $image): self
    {
        if (!$this->images->contains($image)) {
            $this->images[] = $image;
            $image->setProduct($this);
        }

        return $this;
    }

    public function removeImage(ProductImage $image): self
    {
        if ($this->images->removeElement($image)) {
            // set the owning side to null (unless already changed)
            if ($image->getProduct() === $this) {
                $image->setProduct(null);
            }
        }

        return $this;
    }

    public function getMainImageName(): ?string
    {
        return $this->mainImageName;
    }

    public function setMainImageName(?string $mainImageName): self
    {
        $this->mainImageName = $mainImageName;

        return $this;
    }

    public function getCreated(): ?DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getUpdated(): ?DateTimeInterface
    {
        return $this->updated;
    }

    public function setUpdated(DateTimeInterface $updated): self
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedNow(): void
    {
        $this->updated = new DateTime('now');
    }

    public function determineMainImageName(): void
    {
        $this->mainImageName = null;
        $greatestPriority = null;

        foreach ($this->images as $image)
        {
            $imagePriority = $image->getPriority();

            if($greatestPriority === null || $imagePriority > $greatestPriority)
            {
                $greatestPriority = $imagePriority;
                $this->mainImageName = $image->getName();
            }
        }
    }

    /**
     * @return Collection|CartOccurence[]
     */
    public function getCartOccurences(): Collection
    {
        return $this->cartOccurences;
    }

    public function addCartOccurence(CartOccurence $cartOccurence): self
    {
        if (!$this->cartOccurences->contains($cartOccurence)) {
            $this->cartOccurences[] = $cartOccurence;
            $cartOccurence->setProduct($this);
        }

        return $this;
    }

    public function removeCartOccurence(CartOccurence $cartOccurence): self
    {
        if ($this->cartOccurences->removeElement($cartOccurence)) {
            // set the owning side to null (unless already changed)
            if ($cartOccurence->getProduct() === $this) {
                $cartOccurence->setProduct(null);
            }
        }

        return $this;
    }

    public static function getSortDataForAdmin(): array
    {
        return [
            'Od nejnovějších' => 'created'.SortingService::ATTRIBUTE_TAG_DESC,
            'Od nejstarších' => 'created'.SortingService::ATTRIBUTE_TAG_ASC,
            'Název (A-Z)' => 'name'.SortingService::ATTRIBUTE_TAG_ASC,
            'Název (Z-A)' => 'name'.SortingService::ATTRIBUTE_TAG_DESC,
            'Odkaz (A-Z)' => 'slug'.SortingService::ATTRIBUTE_TAG_ASC,
            'Odkaz (Z-A)' => 'slug'.SortingService::ATTRIBUTE_TAG_DESC,
            'Cena bez DPH (vzestupně)' => 'priceWithoutVat'.SortingService::ATTRIBUTE_TAG_ASC,
            'Cena bez DPH (sestupně)' => 'priceWithoutVat'.SortingService::ATTRIBUTE_TAG_DESC,
            'Cena s DPH (vzestupně)' => 'priceWithVat'.SortingService::ATTRIBUTE_TAG_ASC,
            'Cena s DPH (sestupně)' => 'priceWithVat'.SortingService::ATTRIBUTE_TAG_DESC,
            'DPH (vzestupně)' => 'vat'.SortingService::ATTRIBUTE_TAG_ASC,
            'DPH (sestupně)' => 'vat'.SortingService::ATTRIBUTE_TAG_DESC,
            'Ks skladem (vzestupně)' => 'inventory'.SortingService::ATTRIBUTE_TAG_ASC,
            'Ks skladem (sestupně)' => 'inventory'.SortingService::ATTRIBUTE_TAG_DESC,
        ];
    }

    public static function getSortDataForCatalog(): array
    {
        return [
            'Od nejnovějších' => 'created'.SortingService::ATTRIBUTE_TAG_DESC,
            'Od nejstarších' => 'created'.SortingService::ATTRIBUTE_TAG_ASC,
            'Název (A-Z)' => 'name'.SortingService::ATTRIBUTE_TAG_ASC,
            'Název (Z-A)' => 'name'.SortingService::ATTRIBUTE_TAG_DESC,
            'Cena (vzestupně)' => 'priceWithVat'.SortingService::ATTRIBUTE_TAG_ASC,
            'Cena (sestupně)' => 'priceWithVat'.SortingService::ATTRIBUTE_TAG_DESC,
            'Ks skladem (vzestupně)' => 'inventory'.SortingService::ATTRIBUTE_TAG_ASC,
            'Ks skladem (sestupně)' => 'inventory'.SortingService::ATTRIBUTE_TAG_DESC,
        ];
    }
}