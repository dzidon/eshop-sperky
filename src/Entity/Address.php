<?php

namespace App\Entity;

use App\Repository\AddressRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=AddressRepository::class)
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
     * @Assert\Choice(choices={Address::COUNTRY_CODE_CZ, Address::COUNTRY_CODE_SK}, message="Zvolte platnou zemi.")
     */
    private $country;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Length(
     *      min = 1,
     *      max = 255,
     *      minMessage = "Minimální počet znaků: {{ limit }}",
     *      maxMessage = "Maximální počet znaků: {{ limit }}")
     * @Assert\NotBlank(message = "Zadejte ulici a číslo popisné.")
     */
    private $street;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Length(
     *      min = 1,
     *      max = 255,
     *      minMessage = "Minimální počet znaků: {{ limit }}",
     *      maxMessage = "Maximální počet znaků: {{ limit }}")
     * @Assert\NotBlank(message = "Zadejte město.")
     */
    private $town;

    /**
     * @ORM\Column(type="string", length=5)
     * @Assert\Regex(
     *     pattern="/^\d{5}$/",
     *     message="PSČ musí být ve tvaru pěti číslic bez mezery.")
     * @Assert\NotBlank(message = "Zadejte PSČ.")
     */
    private $zip;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(
     *      max = 255,
     *      maxMessage = "Maximální počet znaků: {{ limit }}")
     */
    private $company;

    /**
     * @ORM\Column(type="string", length=8, nullable=true)
     * @Assert\Regex(
     *     pattern="/^\d{8}$/",
     *     message="IČ musí být ve tvaru osmi číslic bez mezery.")
     */
    private $ic;

    /**
     * @ORM\Column(type="string", length=12, nullable=true)
     * @Assert\Length(
     *      min = 10,
     *      max = 12,
     *      minMessage = "Minimální počet znaků: {{ limit }}",
     *      maxMessage = "Maximální počet znaků: {{ limit }}")
     */
    private $dic;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="addresses")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Length(
     *      max = 255,
     *      maxMessage = "Maximální počet znaků: {{ limit }}")
     * @Assert\NotBlank(message = "Zadejte alias.")
     */
    private $alias;

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

    public function setCountry(string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(string $street): self
    {
        $this->street = $street;

        return $this;
    }

    public function getTown(): ?string
    {
        return $this->town;
    }

    public function setTown(string $town): self
    {
        $this->town = $town;

        return $this;
    }

    public function getZip(): ?string
    {
        return $this->zip;
    }

    public function setZip(string $zip): self
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

    public function setAlias(string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }
}
