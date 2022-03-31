<?php

namespace App\Entity;

use App\Repository\DeliveryMethodRepository;
use App\Service\SortingService;
use DateTime;
use DateTimeInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validation\Compound as AssertCompound;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity(repositoryClass=DeliveryMethodRepository::class)
 * @ORM\HasLifecycleCallbacks()
 * @Vich\Uploadable
 */
class DeliveryMethod
{
    public const TYPE_CZECH_POST = 'CZECH_POST';
    public const TYPE_PACKETA_CZ = 'PACKETA_CZ';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Assert\Length(max=255, maxMessage="Maximální počet znaků v názvu doručovací metody: {{ limit }}")
     * @Assert\NotBlank
     */
    private $name;

    /**
     * @Vich\UploadableField(mapping="delivery_method_images", fileNameProperty="imagePath")
     *
     * @Assert\File(
     *     mimeTypes = {"image/jpeg", "image/png"},
     *     mimeTypesMessage = "Můžete nahrávat pouze následující formáty: PNG, JPG, JPEG, JPE.")
     */
    private $imageFile;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $imagePath;

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
     * @ORM\OneToMany(targetEntity=Order::class, mappedBy="deliveryMethod")
     */
    private $orders;

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

        $this->orders = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
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

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function setImagePath(?string $imagePath): self
    {
        $this->imagePath = $imagePath;

        return $this;
    }

    public function getPriceWithoutVat(): ?float
    {
        return $this->priceWithoutVat;
    }

    public function setPriceWithoutVat(float $priceWithoutVat): self
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

    public function getVat(): ?float
    {
        return $this->vat;
    }

    public function setVat(float $vat): self
    {
        $this->vat = $vat;

        return $this;
    }

    public function setImageFile(?File $imageFile = null): void
    {
        $this->updated = new DateTime('now');
        $this->imageFile = $imageFile;
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    /**
     * @return Collection|Order[]
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): self
    {
        if (!$this->orders->contains($order)) {
            $this->orders[] = $order;
            $order->setDeliveryMethod($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): self
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getDeliveryMethod() === $this) {
                $order->setDeliveryMethod(null);
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

    /**
     * @ORM\PreFlush
     */
    public function calculatePriceWithVat(): self
    {
        $this->priceWithVat = $this->priceWithoutVat * (1 + $this->vat);

        return $this;
    }

    public static function getSortData(): array
    {
        return [
            'Od nejnovějších' => 'created'.SortingService::ATTRIBUTE_TAG_DESC,
            'Od nejstarších' => 'created'.SortingService::ATTRIBUTE_TAG_ASC,
            'Název (A-Z)' => 'name'.SortingService::ATTRIBUTE_TAG_ASC,
            'Název (Z-A)' => 'name'.SortingService::ATTRIBUTE_TAG_DESC,
            'Cena vč. DPH (vzestupně)' => 'priceWithVat'.SortingService::ATTRIBUTE_TAG_ASC,
            'Cena vč. DPH (sestupně)' => 'priceWithVat'.SortingService::ATTRIBUTE_TAG_DESC,
        ];
    }
}
