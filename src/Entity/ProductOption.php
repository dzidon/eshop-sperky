<?php

namespace App\Entity;

use App\Repository\ProductOptionRepository;
use App\Service\SortingService;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=ProductOptionRepository::class)
 * @ORM\HasLifecycleCallbacks()
 */
class ProductOption
{
    public const TYPE_NUMBER = 'Číslo';
    public const TYPE_DROPDOWN = 'Rozbalovací seznam';
    public const TYPES = [self::TYPE_NUMBER, self::TYPE_DROPDOWN];
    public const TYPE_NAMES = [
        self::TYPE_NUMBER => self::TYPE_NUMBER,
        self::TYPE_DROPDOWN => self::TYPE_DROPDOWN,
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
        $this->created = new DateTime('now');
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

    public function isConfigured(): ?bool
    {
        return $this->isConfigured;
    }

    /**
     * @ORM\PreFlush
     */
    public function setConfiguredIfValid(): void
    {
        $this->isConfigured = false;

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
                $parameterName = $parameter->getName();
                if (isset($found[$parameterName]))
                {
                    $found[$parameterName] = true;
                }
            }

            $booleans = array_values($found);
            if(count(array_unique($booleans)) === 1 && $booleans[0] === true) //všechny parametry jsou nastaveny
            {
                $this->isConfigured = true;
            }
        }
        else if($this->type === self::TYPE_DROPDOWN)
        {
            if($this->getParameterByName('item'))
            {
                $this->isConfigured = true;
            }
        }
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
            'Od nejnovějších' => 'created'.SortingService::ATTRIBUTE_TAG_DESC,
            'Od nejstarších' => 'created'.SortingService::ATTRIBUTE_TAG_ASC,
            'Název (A-Z)' => 'name'.SortingService::ATTRIBUTE_TAG_ASC,
            'Název (Z-A)' => 'name'.SortingService::ATTRIBUTE_TAG_DESC,
            'Typ (A-Z)' => 'type'.SortingService::ATTRIBUTE_TAG_ASC,
            'Typ (Z-A)' => 'type'.SortingService::ATTRIBUTE_TAG_DESC,
            'Od nakonfigurovaných' => 'isConfigured'.SortingService::ATTRIBUTE_TAG_DESC,
            'Od nenakonfigurovaných' => 'isConfigured'.SortingService::ATTRIBUTE_TAG_ASC,
        ];
    }
}
