<?php

namespace App\Entity\Abstraction;

/**
 * Interface pro entity, které mají e-mail.
 *
 * @package App\Entity\Abstraction
 */
interface EntityEmailInterface
{
    public function getEmail(): ?string;

    public function setEmail(?string $email): self;
}