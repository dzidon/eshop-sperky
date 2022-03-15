<?php

namespace App\Entity;

use Symfony\Component\Uid\Uuid;
use App\Repository\OrderRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=OrderRepository::class)
 * @ORM\HasLifecycleCallbacks()
 */
class Order
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="uuid")
     */
    private $token;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function setToken($token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @ORM\PreFlush
     */
    public function generateTokenIfNull(): self
    {
        if($this->token === null)
        {
            $this->token = Uuid::v4();
        }

        return $this;
    }
}
