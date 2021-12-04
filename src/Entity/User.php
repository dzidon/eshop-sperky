<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @UniqueEntity(fields={"email"}, message="Už existuje účet s tímto e-mailem.")
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const GENDER_ID_MALE = false;
    public const GENDER_ID_FEMALE = true;
    public const GENDER_NAME_MALE = 'Pan';
    public const GENDER_NAME_FEMALE = 'Paní';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     *
     * Validace se resi pres UserEmailType
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
     * @ORM\Column(type="boolean", options={"default" : 0})
     *
     * Validace se resi pres UserGenderType
     */
    private $gender;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * Validace se resi pres UserFirstNameType
     */
    private $nameFirst;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * Validace se resi pres UserLastNameType
     */
    private $nameLast;

    /**
     * @ORM\Column(type="phone_number", nullable=true)
     */
    private $phoneNumber;

    /**
     * @ORM\OneToMany(targetEntity=Address::class, mappedBy="user", orphanRemoval=true)
     */
    private $addresses;

    public function __construct()
    {
        $this->addresses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
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

    public function setPassword(string $password): self
    {
        $this->password = $password;

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
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
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

    public function getVerifyLinkLastSent(): ?\DateTimeInterface
    {
        return $this->verifyLinkLastSent;
    }

    public function setVerifyLinkLastSent(?\DateTimeInterface $verifyLinkLastSent): self
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
            $date2 = new \DateTime( 'now' );

            if(($date2->getTimestamp() - $date1->getTimestamp()) < $minTimeDiffSeconds)
            {
                return false;
            }
        }

        return true;
    }

    public function getRegistered(): ?\DateTimeInterface
    {
        return $this->registered;
    }

    public function setRegistered(\DateTimeInterface $registered): self
    {
        $this->registered = $registered;

        return $this;
    }

    public function getGender(): ?bool
    {
        return $this->gender;
    }

    /**
     * Vrací buď 'Muž' nebo 'Žena'
     *
     * @return string
     */
    public function getGenderName(): string
    {
        if($this->gender === self::GENDER_ID_MALE)
        {
            return self::GENDER_NAME_MALE;
        }
        else
        {
            return self::GENDER_NAME_FEMALE;
        }
    }

    public function setGender(bool $gender): self
    {
        $this->gender = $gender;

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
        if (!$this->addresses->contains($address)) {
            $this->addresses[] = $address;
            $address->setUser($this);
        }

        return $this;
    }

    public function removeAddress(Address $address): self
    {
        if ($this->addresses->removeElement($address)) {
            // set the owning side to null (unless already changed)
            if ($address->getUser() === $this) {
                $address->setUser(null);
            }
        }

        return $this;
    }
}