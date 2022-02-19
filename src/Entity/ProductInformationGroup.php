<?php

namespace App\Entity;

use App\Repository\ProductInformationGroupRepository;
use App\Service\SortingService;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass=ProductInformationGroupRepository::class)
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(fields={"name"}, message="Už existuje skupina produktových informací s tímto názvem.")
 */
class ProductInformationGroup
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
     * @Assert\Length(max=255, maxMessage="Maximální počet znaků v názvu skupiny produktových informací: {{ limit }}")
     * @Assert\NotBlank
     */
    private $name;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updated;

    /**
     * @ORM\OneToMany(targetEntity=ProductInformation::class, mappedBy="productInformationGroup", cascade={"persist"})
     */
    private $info;

    public function __construct()
    {
        $this->created = new DateTime('now');
        $this->updated = $this->created;

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
            $info->setProductInformationGroup($this);
        }

        return $this;
    }

    public function removeInfo(ProductInformation $info): self
    {
        if ($this->info->removeElement($info)) {
            // set the owning side to null (unless already changed)
            if ($info->getProductInformationGroup() === $this) {
                $info->setProductInformationGroup(null);
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
            'Název (A-Z)' => 'name'.SortingService::ATTRIBUTE_TAG_ASC,
            'Název (Z-A)' => 'name'.SortingService::ATTRIBUTE_TAG_DESC,
            'Od nejstarší' => 'created'.SortingService::ATTRIBUTE_TAG_ASC,
            'Od nejnovější' => 'created'.SortingService::ATTRIBUTE_TAG_DESC,
            'Od naposledy upravené' => 'updated'.SortingService::ATTRIBUTE_TAG_DESC,
            'Od poprvé upravené' => 'updated'.SortingService::ATTRIBUTE_TAG_ASC,
        ];
    }
}
