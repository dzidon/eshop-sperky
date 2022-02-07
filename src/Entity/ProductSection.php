<?php

namespace App\Entity;

use App\Entity\Interfaces\UpdatableEntityInterface;
use App\Repository\ProductSectionRepository;
use App\Service\SortingService;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass=ProductSectionRepository::class)
 * @UniqueEntity(fields={"slug"}, message="Už existuje produktová sekce s tímto názvem pro odkaz.")
 */
class ProductSection implements UpdatableEntityInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Assert\Length(max=255, maxMessage="Maximální počet znaků v názvu sekce: {{ limit }}")
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
     * @ORM\Column(type="boolean")
     *
     * @Assert\Type("bool")
     */
    private $isHidden = false;

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
        $this->created = new \DateTime('now');
        $this->updated = $this->created;
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

    public function isHidden(): ?bool
    {
        return $this->isHidden;
    }

    public function setIsHidden(?bool $isHidden): self
    {
        $this->isHidden = $isHidden;

        return $this;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getUpdated(): ?\DateTimeInterface
    {
        return $this->updated;
    }

    public function setUpdated(\DateTimeInterface $updated): self
    {
        $this->updated = $updated;

        return $this;
    }

    public static function getSortData(): array
    {
        return [
            'Název (A-Z)' => 'name'.SortingService::ATTRIBUTE_TAG_ASC,
            'Název (Z-A)' => 'name'.SortingService::ATTRIBUTE_TAG_DESC,
            'Odkaz (A-Z)' => 'slug'.SortingService::ATTRIBUTE_TAG_ASC,
            'Odkaz (Z-A)' => 'slug'.SortingService::ATTRIBUTE_TAG_DESC,
            'Od skrytých' => 'isHidden'.SortingService::ATTRIBUTE_TAG_DESC,
            'Od nejstarší' => 'created'.SortingService::ATTRIBUTE_TAG_ASC,
            'Od nejnovější' => 'created'.SortingService::ATTRIBUTE_TAG_DESC,
            'Od naposledy upravené' => 'updated'.SortingService::ATTRIBUTE_TAG_DESC,
            'Od poprvé upravené' => 'updated'.SortingService::ATTRIBUTE_TAG_ASC,
        ];
    }
}
