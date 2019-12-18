<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TemporaryListRepository")
 */
class TemporaryList
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $mark;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $model;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $version;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $discount_min;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $discount_max;

    /**
     * @ORM\Column(type="float")
     */
    private $price;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $body;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $active_from;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $active_to;

    /**
     * @ORM\Column(type="bigint", nullable=true)
     */
    private $image;

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

    public function setVersion(string $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function getDiscountMin(): ?float
    {
        return $this->discount_min;
    }

    public function setDiscountMin(float $discount_min): self
    {
        $this->discount_min = $discount_min;

        return $this;
    }

    public function getDiscountMax(): ?float
    {
        return $this->discount_max;
    }

    public function setDiscountMax(float $discount_max): self
    {
        $this->discount_max = $discount_max;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;

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

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;

        return $this;
    }
}
