<?php

namespace App\Entity;

use App\Entity\Detached\Search\Atomic\Sort;
use App\Repository\UserRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validation\Compound as AssertCompound;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface, EntityEmailInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     *
     * @AssertCompound\EmailRequirements(groups={"validateEmail"})
     * @Assert\NotBlank(groups={"validateEmail"})
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $password;

    /**
     * @Assert\Length(min=6, minMessage="Minimální počet znaků v hesle: {{ limit }}",
     *                max=4096, maxMessage="Maximální počet znaků v hesle: {{ limit }}",
     *                groups={"validateNewPassword"})
     * @Assert\NotBlank(groups={"validateNewPassword"})
     */
    private $plainPassword;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isVerified = false;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $facebookId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $googleId;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $verifyLinkLastSent;

    /**
     * @ORM\Column(type="datetime")
     */
    private $registered;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Assert\Length(max=255, maxMessage="Maximální počet znaků v křestním jméně: {{ limit }}")
     */
    private $nameFirst;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Assert\Length(max=255, maxMessage="Maximální počet znaků v příjmení: {{ limit }}")
     */
    private $nameLast;

    /**
     * @ORM\Column(type="phone_number", nullable=true)
     */
    private $phoneNumber;

    /**
     * @ORM\OneToMany(targetEntity=Address::class, mappedBy="user", cascade={"persist"})
     */
    private $addresses;

    /**
     * @ORM\OneToOne(targetEntity=Review::class, mappedBy="user")
     */
    private $review;

    /**
     * @ORM\ManyToMany(targetEntity=Permission::class)
     * @ORM\JoinTable(name="_user_permission")
     */
    private $permissions;

    /**
     * @ORM\Column(type="boolean", options={"default" : 0})
     */
    private $isMuted = false;

    public function __construct()
    {
        $this->registered = new DateTime('now');

        $this->addresses = new ArrayCollection();
        $this->permissions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        $this->plainPassword = null;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(?bool $isVerified): self
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getFacebookId(): ?string
    {
        return $this->facebookId;
    }

    public function setFacebookId(?string $facebookId): self
    {
        $this->facebookId = $facebookId;

        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): self
    {
        $this->googleId = $googleId;

        return $this;
    }

    public function getVerifyLinkLastSent(): ?DateTimeInterface
    {
        return $this->verifyLinkLastSent;
    }

    public function setVerifyLinkLastSent(DateTimeInterface $verifyLinkLastSent): self
    {
        $this->verifyLinkLastSent = $verifyLinkLastSent;

        return $this;
    }

    /**
     * Vezme aktuální timestamp a odečte od něj timestamp posledního odeslání ověřovacího emailu. Pokud je rozdíl těchto
     * hodnot větší než minTimeDiffSeconds, vrátí se true (uživateli můžeme poslat další odkaz).
     * Pokud má uživatel nastavené datum posledního odeslání potvrzovacího emailu na null, vrátí se true.
     *
     * @param $minTimeDiffSeconds
     * @return bool
     */
    public function canSendAnotherVerifyLink($minTimeDiffSeconds): bool
    {
        $date1 = $this->getVerifyLinkLastSent();
        if($date1 !== null)
        {
            $date2 = new DateTime( 'now' );

            if(($date2->getTimestamp() - $date1->getTimestamp()) < $minTimeDiffSeconds)
            {
                return false;
            }
        }

        return true;
    }

    public function getRegistered(): ?DateTimeInterface
    {
        return $this->registered;
    }

    public function setRegistered(DateTimeInterface $registered): self
    {
        $this->registered = $registered;

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
     * Vrátí true, pokud je nastavené jméno a příjmení zároveň
     *
     * @return bool
     */
    public function fullNameIsSet(): bool
    {
        return $this->getNameFirst() !== null && $this->getNameLast() !== null;
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

    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber($phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    /**
     * @return Collection|Address[]
     */
    public function getAddresses(): Collection
    {
        return $this->addresses;
    }

    public function addAddress(Address $address): self
    {
        if (!$this->addresses->contains($address))
        {
            $this->addresses[] = $address;
            $address->setUser($this);
        }

        return $this;
    }

    public function removeAddress(Address $address): self
    {
        $this->addresses->removeElement($address);

        return $this;
    }

    public function getReview(): ?Review
    {
        return $this->review;
    }

    public function setReview(Review $review): self
    {
        if ($review->getUser() !== $this)
        {
            $review->setUser($this);
        }

        $this->review = $review;

        return $this;
    }

    /**
     * @return Collection|Permission[]
     */
    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    public function getPermissionsGrouped(): array
    {
        $permissionsGrouped = array();
        foreach ($this->permissions as $permission)
        {
            $permissionsGrouped[$permission->getCategory()][] = $permission;
        }

        return $permissionsGrouped;
    }

    public function addPermission(Permission $permission): self
    {
        if (!$this->permissions->contains($permission))
        {
            $this->permissions[] = $permission;
        }

        return $this;
    }

    public function removePermission(Permission $permission): self
    {
        $this->permissions->removeElement($permission);

        return $this;
    }

    /**
     * Vrátí true, pokud má uživatel oprávnění s daným kódem
     *
     * @param string $code
     * @return bool
     */
    public function hasPermission(string $code): bool
    {
        foreach ($this->permissions as $permission)
        {
            if($permission->getCode() === $code)
            {
                return true;
            }
        }

        return false;
    }

    public function isMuted(): ?bool
    {
        return $this->isMuted;
    }

    public function setIsMuted(bool $isMuted): self
    {
        $this->isMuted = $isMuted;

        return $this;
    }

    public static function getSortData(): array
    {
        return [
            'Od nejnovějších' => 'registered'.Sort::ATTRIBUTE_TAG_DESC,
            'Od nejstarších' => 'registered'.Sort::ATTRIBUTE_TAG_ASC,
            'E-mail (A-Z)' => 'email'.Sort::ATTRIBUTE_TAG_ASC,
            'E-mail (Z-A)' => 'email'.Sort::ATTRIBUTE_TAG_DESC,
            'Křestní jméno (A-Z)' => 'nameFirst'.Sort::ATTRIBUTE_TAG_ASC,
            'Křestní jméno (Z-A)' => 'nameFirst'.Sort::ATTRIBUTE_TAG_DESC,
            'Příjmení (A-Z)' => 'nameLast'.Sort::ATTRIBUTE_TAG_ASC,
            'Příjmení (Z-A)' => 'nameLast'.Sort::ATTRIBUTE_TAG_DESC,
            'Od ověřených' => 'isVerified'.Sort::ATTRIBUTE_TAG_DESC,
            'Od neověřených' => 'isVerified'.Sort::ATTRIBUTE_TAG_ASC,
            'Od umlčených' => 'isMuted'.Sort::ATTRIBUTE_TAG_DESC,
            'Od neumlčených' => 'isMuted'.Sort::ATTRIBUTE_TAG_ASC,
        ];
    }
}