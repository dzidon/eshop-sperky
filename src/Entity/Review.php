<?php

namespace App\Entity;

use App\Entity\Detached\Search\Atomic\Sort;
use App\Repository\ReviewRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=ReviewRepository::class)
 * @ORM\Table(indexes={@ORM\Index(name="search_idx", columns={"created", "stars"})})
 * @ORM\HasLifecycleCallbacks()
 */
class Review
{
    public const LENGTH_BEFORE_TRUNCATION = 200;
    public const STAR_VALUES = [5.0,4.0,3.0,2.0,1.0];
    public const STAR_COUNT = 5;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="float")
     *
     * @Assert\Choice(choices=Review::STAR_VALUES, message="Zvolte platné hodnocení.")
     * @Assert\NotBlank(message="Vyberte hodnocení.")
     */
    private $stars;

    /**
     * @ORM\Column(type="string", length=1000, nullable=true)
     *
     * @Assert\Length(max=1000, maxMessage="Maximální počet znaků v textu recenze: {{ limit }}")
     */
    private $text;

    /**
     * @ORM\OneToOne(targetEntity=User::class, inversedBy="review")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $user;

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
        $user->setReview($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStars(): ?float
    {
        return $this->stars;
    }

    public function setStars(?float $stars): self
    {
        $this->stars = $stars;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): self
    {
        $this->text = $text;

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

    public function isTruncationReady(): bool
    {
        return mb_strlen($this->text, 'utf-8') > self::LENGTH_BEFORE_TRUNCATION;
    }

    public static function getSortData(): array
    {
        return [
            'Od nejnovějších' => 'created'.Sort::ATTRIBUTE_TAG_DESC,
            'Od nejstarších' => 'created'.Sort::ATTRIBUTE_TAG_ASC,
            'Počet hvězd (vzestupně)' => 'stars'.Sort::ATTRIBUTE_TAG_ASC,
            'Počet hvězd (sestupně)' => 'stars'.Sort::ATTRIBUTE_TAG_DESC,
        ];
    }
}
