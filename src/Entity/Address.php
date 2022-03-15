<?php

namespace App\Entity;

use App\Repository\AddressRepository;
use App\Service\SortingService;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use App\Validation as AssertCustom;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=AddressRepository::class)
 * @ORM\Table(indexes={@ORM\Index(name="search_idx", columns={"alias", "created"})})
 * @ORM\HasLifecycleCallbacks()
 * @AssertCustom\AllOrNone(targetAttributes={"company", "ic", "dic"})
 */
class Address
{
    public const COUNTRY_NAME_CZ = 'Česká republika';
    public const COUNTRY_NAME_SK = 'Slovensko';
    public const COUNTRY_NAMES = [
        self::COUNTRY_NAME_CZ, self::COUNTRY_NAME_SK
    ];
    public const COUNTRY_NAMES_DROPDOWN = [
        self::COUNTRY_NAME_CZ => self::COUNTRY_NAME_CZ,
        self::COUNTRY_NAME_SK => self::COUNTRY_NAME_SK,
    ];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=32)
     *
     * @Assert\Choice(choices=Address::COUNTRY_NAMES, message="Zvolte platnou zemi.")
     * @Assert\NotBlank
     */
    private $country;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @AssertCustom\Compound\StreetRequirements
     * @Assert\NotBlank
     */
    private $street;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Assert\Length(max=255, maxMessage="Maximální počet znaků v obci: {{ limit }}")
     * @Assert\NotBlank
     */
    private $town;

    /**
     * @ORM\Column(type="string", length=5)
     *
     * @AssertCustom\ZipCode
     * @Assert\NotBlank
     */
    private $zip;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Assert\Length(max=255, maxMessage="Maximální počet znaků v názvu firmy: {{ limit }}")
     */
    private $company;

    /**
     * @ORM\Column(type="string", length=8, nullable=true)
     *
     * @AssertCustom\Ic
     */
    private $ic;

    /**
     * @ORM\Column(type="string", length=12, nullable=true)
     *
     * @AssertCustom\Dic
     */
    private $dic;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="addresses")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Assert\Length(max=255, maxMessage="Maximální počet znaků v aliasu: {{ limit }}")
     * @Assert\NotBlank
     */
    private $alias;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Assert\Length(max=255, maxMessage="Maximální počet znaků v doplňku adresy: {{ limit }}")
     */
    private $additionalInfo;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Assert\Length(max=255, maxMessage="Maximální počet znaků v křestním jméně: {{ limit }}")
     * @Assert\NotBlank
     */
    private $nameFirst;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Assert\Length(max=255, maxMessage="Maximální počet znaků v příjmení: {{ limit }}")
     * @Assert\NotBlank
     */
    private $nameLast;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updated;

    public function __construct(User $user)
    {
        $this->created = new DateTime('now');
        $this->updated = $this->created;
        $this->user = $user;

        $this->nameFirst = $this->user->getNameFirst();
        $this->nameLast = $this->user->getNameLast();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(?string $street): self
    {
        $this->street = $street;

        return $this;
    }

    public function getTown(): ?string
    {
        return $this->town;
    }

    public function setTown(?string $town): self
    {
        $this->town = $town;

        return $this;
    }

    public function getZip(): ?string
    {
        return $this->zip;
    }

    public function setZip(?string $zip): self
    {
        $this->zip = preg_replace('/\s+/', '', $zip);

        return $this;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(?string $company): self
    {
        $this->company = $company;

        return $this;
    }

    public function getIc(): ?string
    {
        return $this->ic;
    }

    public function setIc(?string $ic): self
    {
        $this->ic = $ic;

        return $this;
    }

    public function getDic(): ?string
    {
        return $this->dic;
    }

    public function setDic(?string $dic): self
    {
        $this->dic = $dic;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function setAlias(?string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }

    public function getAdditionalInfo(): ?string
    {
        return $this->additionalInfo;
    }

    public function setAdditionalInfo(?string $additionalInfo): self
    {
        $this->additionalInfo = $additionalInfo;

        return $this;
    }

    public function getNameFirst(): ?string
    {
        return $this->nameFirst;
    }

    public function setNameFirst(?string $nameFirst): self
    {
        $this->nameFirst = $nameFirst;

        return $this;
    }

    public function getNameLast(): ?string
    {
        return $this->nameLast;
    }

    public function setNameLast(?string $nameLast): self
    {
        $this->nameLast = $nameLast;

        return $this;
    }

    /**
     * Vrátí celé jméno
     *
     * @return string
     */
    public function getFullName(): string
    {
        return $this->nameFirst . ' ' . $this->nameLast;
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
            'Alias (A-Z)' => 'alias'.SortingService::ATTRIBUTE_TAG_ASC,
            'Alias (Z-A)' => 'alias'.SortingService::ATTRIBUTE_TAG_DESC,
        ];
    }
}
