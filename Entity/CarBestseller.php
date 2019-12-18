<?php

namespace App\Entity;

use App\Repository\ConfiguratorModelRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CarBestsellerRepository")
 */
class CarBestseller
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $mark;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $model;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $version;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $active;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $active_from;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $active_to;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $car_bestseller_order;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $body;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $min_price;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMark(): ?string
    {
        return $this->mark;
    }

    public function setMark(string $mark): self
    {
        $this->mark = $mark;

        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(?string $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(?bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getActiveFrom(): ?\DateTimeInterface
    {
        return $this->active_from;
    }

    public function setActiveFrom(?\DateTimeInterface $active_from): self
    {
        $this->active_from = $active_from;

        return $this;
    }

    public function getActiveTo(): ?\DateTimeInterface
    {
        return $this->active_to;
    }

    public function setActiveTo(?\DateTimeInterface $active_to): self
    {
        $this->active_to = $active_to;

        return $this;
    }

    public function getCarBestsellerOrder(): ?int
    {
        return $this->car_bestseller_order;
    }

    public function setCarBestsellerOrder(?int $car_bestseller_order): self
    {
        $this->car_bestseller_order = $car_bestseller_order;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function getMinPrice(): ?float
    {
        return $this->min_price;
    }

    public function setMinPrice(?float $min_price): self
    {
        $this->min_price = $min_price;

        return $this;
    }
}
