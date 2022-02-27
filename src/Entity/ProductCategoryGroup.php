<?php

namespace App\Entity;

use App\Repository\ProductCategoryGroupRepository;
use App\Service\SortingService;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Validation as AssertCustom;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass=ProductCategoryGroupRepository::class)
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(groups={"creation"}, fields={"name"}, message="Už existuje skupina kategorií s tímto názvem.")
 * @AssertCustom\UniqueEntitiesInCollection(
 *     groups={"creation"},
 *     fieldsOfChildren={"name"},
 *     collectionName="categories",
 *     message="Všechny kategorie obsažené ve skupině musejí mít unikátní názvy.")
 */
class ProductCategoryGroup
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     *
     * @Assert\Length(max=255, maxMessage="Maximální počet znaků v názvu skupiny kategorií: {{ limit }}")
     * @Assert\NotBlank
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity=ProductCategory::class, mappedBy="productCategoryGroup", orphanRemoval=true, cascade={"persist"})
     *
     * @Assert\Valid
     */
    private $categories;

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

        $this->categories = new ArrayCollection();
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
            $category->setProductCategoryGroup($this);
        }

        return $this;
    }

    public function removeCategory(ProductCategory $category): self
    {
        if ($this->categories->removeElement($category)) {
            // set the owning side to null (unless already changed)
            if ($category->getProductCategoryGroup() === $this) {
                $category->setProductCategoryGroup(null);
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
            'Od nejnovějších' => 'created'.SortingService::ATTRIBUTE_TAG_DESC,
            'Od nejstarších' => 'created'.SortingService::ATTRIBUTE_TAG_ASC,
            'Název (A-Z)' => 'name'.SortingService::ATTRIBUTE_TAG_ASC,
            'Název (Z-A)' => 'name'.SortingService::ATTRIBUTE_TAG_DESC,
        ];
    }
}
