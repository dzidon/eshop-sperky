<?php

namespace App\Entity;

use App\Repository\AddressRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Validation as AssertCustom;

/**
 * @ORM\Entity(repositoryClass=AddressRepository::class)
 * @AssertCustom\AllOrNone(targetAttributes={"company", "ic", "dic"})
 */
class Address
{
    public const COUNTRY_CODE_CZ = 'CS';
    public const COUNTRY_CODE_SK = 'SK';
    public const COUNTRY_NAMES = [
        self::COUNTRY_CODE_CZ => 'Česká republika',
        self::COUNTRY_CODE_SK => 'Slovensko',
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
     * Validace se resi pres AddressCountryType
     */
    private $country;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * Validace se resi pres AddressStreetType
     */
    private $street;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * Validace se resi pres AddressTownType
     */
    private $town;

    /**
     * @ORM\Column(type="string", length=5)
     *
     * Validace se resi pres AddressZipType
     */
    private $zip;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * Validace se resi pres AddressCompanyNameType
     */
    private $company;

    /**
     * @ORM\Column(type="string", length=8, nullable=true)
     *
     * Validace se resi pres AddressIcType
     */
    private $ic;

    /**
     * @ORM\Column(type="string", length=12, nullable=true)
     *
     * Validace se resi pres AddressDicType
     */
    private $dic;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="addresses")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * Validace se resi pres AddressDicType
     */
    private $alias;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * Validace se resi pres AddressAdditionalInfoType
     */
    private $additionalInfo;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updated;

    /**
     * Vrátí název země podle kódu
     *
     * @param string $code
     * @return string
     */
    public static function getCountryNameByCode(string $code): string
    {
        return self::COUNTRY_NAMES[$code];
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
        $this->zip = $zip;

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

    public function setUser(?User $user): self
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
}
