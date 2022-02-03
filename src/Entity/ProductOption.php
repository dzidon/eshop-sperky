<?php

namespace App\Entity;

use App\Repository\ProductOptionRepository;
use App\Service\SortingService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=ProductOptionRepository::class)
 */
class ProductOption
{
    public const TYPE_NUMBER = 'number';
    public const TYPE_DROPDOWN = 'dropdown';
    public const TYPES = [self::TYPE_NUMBER, self::TYPE_DROPDOWN];
    public const TYPE_NAMES = [
        'Číslo (number)' => self::TYPE_NUMBER,
        'Rozbalovací seznam (dropdown)' => self::TYPE_DROPDOWN,
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
     * @Assert\Length(max=255, maxMessage="Maximální počet znaků v názvu produktové volby: {{ limit }}")
     * @Assert\NotBlank
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Assert\Choice(choices=ProductOption::TYPES, message="Zvolte platný typ.")
     * @Assert\NotBlank(message="Vyberte typ.")
     */
    private $type;

    /**
     * @ORM\OneToMany(targetEntity=ProductOptionParameter::class, mappedBy="productOption", orphanRemoval=true, cascade={"persist"})
     *
     * @Assert\Valid
     */
    private $parameters;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isConfigured;

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
        $this->parameters = new ArrayCollection();
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

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

    public function isConfigured(): ?bool
    {
        return $this->isConfigured;
    }

    public function setConfiguredIfValid(): self
    {
        $isValid = false;

        if($this->type === self::TYPE_NUMBER)
        {
            $found = [
                'min' => false,
                'max' => false,
                'default' => false,
                'step' => false,
            ];

            foreach ($this->parameters as $parameter)
            {
                if (isset($found[$parameter->getName()]))
                {
                    $found[$parameter->getName()] = true;
                }
            }

            $booleans = array_values($found);
            if(count(array_unique($booleans)) === 1 && $booleans[0] === true)
            {
                $isValid = true;
            }
        }
        else if($this->type === self::TYPE_DROPDOWN)
        {
            $found = 0;
            foreach ($this->parameters as $parameter)
            {
                if($parameter->getName() === 'item')
                {
                    $found++;
                    if($found === 2)
                    {
                        $isValid = true;
                        break;
                    }
                }
            }
        }

        $this->isConfigured = $isValid;
        return $this;
    }

    /**
     * @return Collection|ProductOptionParameter[]
     */
    public function getParameters(): Collection
    {
        return $this->parameters;
    }

    public function addParameter(ProductOptionParameter $parameter): self
    {
        if (!$this->parameters->contains($parameter)) {
            $this->parameters[] = $parameter;
            $parameter->setProductOption($this);
        }

        return $this;
    }

    public function removeParameter(ProductOptionParameter $parameter): self
    {
        if ($this->parameters->removeElement($parameter)) {
            // set the owning side to null (unless already changed)
            if ($parameter->getProductOption() === $this) {
                $parameter->setProductOption(null);
            }
        }

        return $this;
    }

    public function getParameterByName($name): ?ProductOptionParameter
    {
        foreach ($this->parameters as $parameter)
        {
            if($parameter->getName() === $name)
            {
                return $parameter;
            }
        }

        return null;
    }

    public function getParameterValue($name): ?string
    {
        $parameter = $this->getParameterByName($name);
        if($parameter)
        {
            return $parameter->getValue();
        }

        return null;
    }

    public function configure(array $data = []): self
    {
        if($this->type === self::TYPE_NUMBER)
        {
            foreach ($data as $parameterName => $parameterValue)
            {
                $parameter = $this->getParameterByName($parameterName);
                if(!$parameter)
                {
                    $parameter = new ProductOptionParameter();
                    $parameter->setName($parameterName);
                }

                $parameter->setValue($parameterValue);
                $this->addParameter($parameter);
            }
        }
        else if($this->type === self::TYPE_DROPDOWN)
        {
            foreach ($this->parameters as $parameter)
            {
                $parameter->setName('item');
            }
        }

        return $this;
    }

    public static function getSortData(): array
    {
        return [
            'Název (A-Z)' => 'name'.SortingService::ATTRIBUTE_TAG_ASC,
            'Název (Z-A)' => 'name'.SortingService::ATTRIBUTE_TAG_DESC,
            'Typ (A-Z)' => 'type'.SortingService::ATTRIBUTE_TAG_ASC,
            'Typ (Z-A)' => 'type'.SortingService::ATTRIBUTE_TAG_DESC,
            'Od nakonfigurovaných' => 'isConfigured'.SortingService::ATTRIBUTE_TAG_DESC,
            'Od nenakonfigurovaných' => 'isConfigured'.SortingService::ATTRIBUTE_TAG_ASC,
            'Od nejstarší' => 'created'.SortingService::ATTRIBUTE_TAG_ASC,
            'Od nejnovější' => 'created'.SortingService::ATTRIBUTE_TAG_DESC,
        ];
    }
}
