<?php

namespace App\Entity;

use App\Repository\TariffRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TariffRepository::class)]
class Tariff
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private ?string $name = null;

    #[ORM\Column(options: ['default' => 0])]
    private ?float $base_price = 0.0;

    #[ORM\Column(options: ['default' => 0])]
    private ?float $base_dist = 0.0;

    #[ORM\Column(options: ['default' => 0])]
    private ?float $dist_cost = 0.0;

    /**
     * @var Collection<int, Driver>
     */
    #[ORM\OneToMany(targetEntity: Driver::class, mappedBy: 'tariff')]
    private Collection $drivers;

    /**
     * @var Collection<int, Order>
     */
    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'tariff')]
    private Collection $orders;

    public function __construct()
    {
        $this->drivers = new ArrayCollection();
        $this->orders = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getBasePrice(): ?float
    {
        return $this->base_price;
    }

    public function setBasePrice(float $base_price): static
    {
        $this->base_price = $base_price;

        return $this;
    }

    public function getBaseDist(): ?float
    {
        return $this->base_dist;
    }

    public function setBaseDist(float $base_dist): static
    {
        $this->base_dist = $base_dist;

        return $this;
    }

    public function getDistCost(): ?float
    {
        return $this->dist_cost;
    }

    public function setDistCost(float $dist_cost): static
    {
        $this->dist_cost = $dist_cost;

        return $this;
    }

    /**
     * @return Collection<int, Driver>
     */
    public function getDrivers(): Collection
    {
        return $this->drivers;
    }

    public function addDriver(Driver $driver): static
    {
        if (!$this->drivers->contains($driver)) {
            $this->drivers->add($driver);
            $driver->setTariff($this);
        }

        return $this;
    }

    public function removeDriver(Driver $driver): static
    {
        if ($this->drivers->removeElement($driver)) {
            // set the owning side to null (unless already changed)
            if ($driver->getTariff() === $this) {
                $driver->setTariff(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setTariff($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getTariff() === $this) {
                $order->setTariff(null);
            }
        }

        return $this;
    }
}
