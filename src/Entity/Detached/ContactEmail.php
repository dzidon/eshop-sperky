<?php

namespace App\Entity\Detached;

use App\Entity\EntityEmailInterface;
use App\Validation\Compound as AssertCompound;
use Symfony\Component\Validator\Constraints as Assert;

class ContactEmail implements EntityEmailInterface
{
    /**
     * @AssertCompound\EmailRequirements
     * @Assert\NotBlank
     */
    private $email;

    /**
     * @Assert\Length(max = 64, maxMessage = "Maximální počet znaků v předmětu: {{ limit }}")
     * @Assert\NotBlank
     */
    private $subject;

    /**
     * @Assert\Length(max = 4096, maxMessage = "Maximální počet znaků v textu: {{ limit }}")
     * @Assert\NotBlank
     */
    private $text;

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): self
    {
        $this->subject = $subject;

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
}
