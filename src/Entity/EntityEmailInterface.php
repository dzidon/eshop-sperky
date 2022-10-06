<?php

namespace App\Entity;

/**
 * Interface pro entity, které mají e-mail.
 *
 * @package App\Entity
 */
interface EntityEmailInterface
{
    public function getEmail(): ?string;

    public function setEmail(?string $email): self;
}