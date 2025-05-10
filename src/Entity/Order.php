<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 24)]
    private ?string $from_loc = null;

    #[ORM\Column(length: 24)]
    private ?string $dest_loc = null;

    #[ORM\Column]
    private ?float $distance = 0;

    #[ORM\Column]
    private ?float $price = 0;

    #[ORM\Column(options: ["default" => "CURRENT_TIMESTAMP"])]
    private ?\DateTimeImmutable $orderedAt = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    private ?User $user_id = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    private ?Driver $driver = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    private ?Tariff $tariff = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFromLoc(): ?string
    {
        return $this->from_loc;
    }

    public function setFromLoc(string $from_loc): static
    {
        $this->from_loc = $from_loc;

        return $this;
    }

    public function getDestLoc(): ?string
    {
        return $this->dest_loc;
    }

    public function setDestLoc(string $dest_loc): static
    {
        $this->dest_loc = $dest_loc;

        return $this;
    }

    public function getDistance(): ?float
    {
        return $this->distance;
    }

    public function setDistance(float $distance): static
    {
        $this->distance = $distance;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getOrderedAt(): ?\DateTimeImmutable
    {
        return $this->orderedAt;
    }

    public function setOrderedAt(\DateTimeImmutable $orderedAt): static
    {
        $this->orderedAt = $orderedAt;

        return $this;
    }

    public function getUserId(): ?User
    {
        return $this->user_id;
    }

    public function setUserId(?User $user_id): static
    {
        $this->user_id = $user_id;

        return $this;
    }

    public function getDriver(): ?Driver
    {
        return $this->driver;
    }

    public function setDriver(?Driver $driver): static
    {
        $this->driver = $driver;

        return $this;
    }

    public function getTariff(): ?Tariff
    {
        return $this->tariff;
    }

    public function setTariff(?Tariff $tariff): static
    {
        $this->tariff = $tariff;

        return $this;
    }
}
