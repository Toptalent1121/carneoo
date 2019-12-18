<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DealerRepository")
 */
class Dealer
{

    use TimestampableEntity;
    /**
     * This defined exception behaviour for Datatable server side function
     * Note: this should be public
     */
    public $exceptionColumns = [
        'createdAt',
        'updatedAt'
    ];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="array")
     */
    private $person = [];

    /**
     * @ORM\Column(type="array")
     */
    private $mail = [];

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $phone;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $fax;

    /**
     * @ORM\Column(type="integer")
     */
    private $zip;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $city;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $address;

    /**
     * @ORM\Column(type="boolean")
     */
    private $active;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Admin")
     * @ORM\JoinColumn(name="created_by", nullable=false)
     */
    private $created_by;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Admin")
     * @ORM\JoinColumn(name="updated_by",nullable=true)
     */
    private $updated_by;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Discount", mappedBy="dealer")
     */
    private $discounts;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $front_description;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $carneo_provision;

    /**
     * @ORM\Column(type="boolean")
     */
    private $stock = false;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Stock", mappedBy="dealer")
     */
    private $stocks;

    public function __construct()
    {
        $this->discounts = new ArrayCollection();
        $this->stocks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPerson(): ?array
    {
        return $this->person;
    }

    public function setPerson(array $person): self
    {
        $this->person = $person;

        return $this;
    }

    public function getMail(): ?array
    {
        return $this->mail;
    }

    public function setMail(array $mail): self
    {
        $this->mail = $mail;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getFax(): ?string
    {
        return $this->fax;
    }

    public function setFax(?string $fax): self
    {
        $this->fax = $fax;

        return $this;
    }

    public function getZip(): ?int
    {
        return $this->zip;
    }

    public function setZip(int $zip): self
    {
        $this->zip = $zip;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getCreatedBy(): ?Admin
    {
        return $this->created_by;
    }

    public function setCreatedBy(?Admin $created_by): self
    {
        $this->created_by = $created_by;

        return $this;
    }

    public function getUpdatedBy(): ?Admin
    {
        return $this->updated_by;
    }

    public function setUpdatedBy(?Admin $updated_by): self
    {
        $this->updated_by = $updated_by;

        return $this;
    }

    /**
     * @return Collection|Discount[]
     */
    public function getDiscounts(): Collection
    {
        return $this->discounts;
    }

    public function addDiscount(Discount $discount): self
    {
        if (!$this->discounts->contains($discount)) {
            $this->discounts[] = $discount;
            $discount->addDealer($this);
        }

        return $this;
    }

    public function removeDiscount(Discount $discount): self
    {
        if ($this->discounts->contains($discount)) {
            $this->discounts->removeElement($discount);
            $discount->removeDealer($this);
        }

        return $this;
    }

    public function getFrontDescription(): ?string
    {
        return $this->front_description;
    }

    public function setFrontDescription(?string $front_description): self
    {
        $this->front_description = $front_description;

        return $this;
    }

    public function getCarneoProvision(): ?float
    {
        return $this->carneo_provision;
    }

    public function setCarneoProvision(?float $carneo_provision): self
    {
        $this->carneo_provision = $carneo_provision;

        return $this;
    }

    public function getStock(): ?bool
    {
        return $this->stock;
    }

    public function setStock(bool $stock): self
    {
        $this->stock = $stock;

        return $this;
    }

    /**
     * @return Collection|Stock[]
     */
    public function getStocks(): Collection
    {
        return $this->stocks;
    }

    public function addStock(Stock $stock): self
    {
        if (!$this->stocks->contains($stock)) {
            $this->stocks[] = $stock;
            $stock->setDealer($this);
        }

        return $this;
    }

    public function removeStock(Stock $stock): self
    {
        if ($this->stocks->contains($stock)) {
            $this->stocks->removeElement($stock);
            // set the owning side to null (unless already changed)
            if ($stock->getDealer() === $this) {
                $stock->setDealer(null);
            }
        }

        return $this;
    }
}