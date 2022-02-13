<?php

namespace App\Entity\Interfaces;

use DateTimeInterface;

interface UpdatableEntityInterface
{
    public function getCreated(): ?DateTimeInterface;

    public function setCreated(DateTimeInterface $created): self;

    public function getUpdated(): ?DateTimeInterface;

    public function setUpdated(DateTimeInterface $updated): self;
}