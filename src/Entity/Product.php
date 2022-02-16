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

/**
 * @ORM\Entity(repositoryClass=ProductRepository::class)
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
     * @Assert\Type("numeric", message="Musíte zadat číselnou hodnotu.")
     * @Assert\Choice(choices=Product::VAT_VALUES, message="Zvolte platnou hodnotu DPH.")
     * @Assert\NotBlank
     */
    private $vat;

    /**
     * @ORM\Column(type="string", length=4096, nullable=true)
     *
     * @Assert\Length(max=4096, maxMessage="Maximální počet znaků v popisu: {{ limit }}")
     */
    private $description;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Assert\Type("bool", message="Zadávaná hodnota není platná.")
     */
    private $isHidden = false;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @Assert\Type("DateTime", message="Musíte zadat datum a čas.")
     */
    private $availableSince;

    /**
     * @ORM\ManyToOne(targetEntity=ProductSection::class)
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $section;

    /**
     * @ORM\ManyToMany(targetEntity=ProductCategory::class)
     * @ORM\JoinTable(name="_product_category")
     */
    private $categories;

    /**
     * @ORM\ManyToMany(targetEntity=ProductOption::class)
     * @ORM\JoinTable(name="_product_option")
     */
    private $options;

    /**
     * @ORM\OneToMany(targetEntity=ProductInformation::class, mappedBy="product", cascade={"persist"})
     */
    private $info;

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

        $this->options = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->info = new ArrayCollection();
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

    public function setPriceWithoutVat($priceWithoutVat): self
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

    public function getAvailableSince(): ?DateTimeInterface
    {
        return $this->availableSince;
    }

    public function setAvailableSince(?DateTimeInterface $availableSince): self
    {
        $this->availableSince = $availableSince;

        return $this;
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
        if (!$this->categories->contains($category)) {
            $this->categories[] = $category;
        }

        return $this;
    }

    public function removeCategory(ProductCategory $category): self
    {
        $this->categories->removeElement($category);

        return $this;
    }

    /**
     * @return Collection|ProductOption[]
     */
    public function getOptions(): Collection
    {
        return $this->options;
    }

    public function addOption(ProductOption $option): self
    {
        if (!$this->options->contains($option)) {
            $this->options[] = $option;
        }

        return $this;
    }

    public function removeOption(ProductOption $option): self
    {
        $this->options->removeElement($option);

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

    public static function getSortData(): array
    {
        return [
            'admin' => [
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
                'Od manuálně skrytých' => 'isHidden'.SortingService::ATTRIBUTE_TAG_DESC,
                'Od manuálně neskrytých' => 'isHidden'.SortingService::ATTRIBUTE_TAG_ASC,
                'Od nejpozdějšího datumu dostupnosti' => 'availableSince'.SortingService::ATTRIBUTE_TAG_DESC,
                'Od nejstaršího' => 'created'.SortingService::ATTRIBUTE_TAG_ASC,
                'Od nejnovějšího' => 'created'.SortingService::ATTRIBUTE_TAG_DESC,
                'Od naposledy upraveného' => 'updated'.SortingService::ATTRIBUTE_TAG_DESC,
                'Od poprvé upraveného' => 'updated'.SortingService::ATTRIBUTE_TAG_ASC,
            ],
            'catalog' => [
                'Název (A-Z)' => 'name'.SortingService::ATTRIBUTE_TAG_ASC,
                'Název (Z-A)' => 'name'.SortingService::ATTRIBUTE_TAG_DESC,
                'Cena (vzestupně)' => 'priceWithVat'.SortingService::ATTRIBUTE_TAG_ASC,
                'Cena (sestupně)' => 'priceWithVat'.SortingService::ATTRIBUTE_TAG_DESC,
                'Od nejstaršího' => 'created'.SortingService::ATTRIBUTE_TAG_ASC,
                'Od nejnovějšího' => 'created'.SortingService::ATTRIBUTE_TAG_DESC,
            ],
        ];
    }
}