<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\OfferRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Offer
{
    use TimestampableEntity;
	
	/**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="bigint", nullable=true))
     */
    private $version;

    /**
     * @ORM\Column(type="integer", nullable=true))
     */
    private $color;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $packet = [];

    /**
     * @ORM\Column(type="integer", nullable=true))
     */
    private $rim;

    /**
     * @ORM\Column(type="integer", nullable=true))
     */
    private $polster;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $exterior = [];

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $audio = [];

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $safety = [];

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $misc = [];

    /**
     * @ORM\Column(type="integer", nullable=true))
     */
    private $main_discount;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Discount")
     */
    private $additional_discount;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="offers")
     */
    private $user;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2)
     */
    private $price;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $call_back;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $financing;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $comment;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $financial_options;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $valid_to;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Stock", inversedBy="offers")
     */
    private $stock;

    public function __construct()
    {
        $this->additional_discount = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVersion(): ?int
    {
        return $this->version;
    }

    public function setVersion(int $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function getColor(): ?int
    {
        return $this->color;
    }

    public function setColor(int $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function getPacket(): ?array
    {
        return $this->packet;
    }

    public function setPacket(?array $packet): self
    {
        $this->packet = $packet;

        return $this;
    }

    public function getRim(): ?int
    {
        return $this->rim;
    }

    public function setRim(int $rim): self
    {
        $this->rim = $rim;

        return $this;
    }

    public function getPolster(): ?int
    {
        return $this->polster;
    }

    public function setPolster(int $polster): self
    {
        $this->polster = $polster;

        return $this;
    }

    public function getExterior(): ?array
    {
        return $this->exterior;
    }

    public function setExterior(?array $exterior): self
    {
        $this->exterior = $exterior;

        return $this;
    }

    public function getAudio(): ?array
    {
        return $this->audio;
    }

    public function setAudio(?array $audio): self
    {
        $this->audio = $audio;

        return $this;
    }

    public function getSafety(): ?array
    {
        return $this->safety;
    }

    public function setSafety(?array $safety): self
    {
        $this->safety = $safety;

        return $this;
    }

    public function getMisc(): ?array
    {
        return $this->misc;
    }

    public function setMisc(?array $misc): self
    {
        $this->misc = $misc;

        return $this;
    }

    public function getMainDiscount(): ?int
    {
        return $this->main_discount;
    }

    public function setMainDiscount(int $main_discount): self
    {
        $this->main_discount = $main_discount;

        return $this;
    }

    /**
     * @return Collection|Discount[]
     */
    public function getAdditionalDiscount(): Collection
    {
        return $this->additional_discount;
    }

    public function addAdditionalDiscount(Discount $additionalDiscount): self
    {
        if (!$this->additional_discount->contains($additionalDiscount)) {
            $this->additional_discount[] = $additionalDiscount;
        }

        return $this;
    }

    public function removeAdditionalDiscount(Discount $additionalDiscount): self
    {
        if ($this->additional_discount->contains($additionalDiscount)) {
            $this->additional_discount->removeElement($additionalDiscount);
        }

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function setPrice($price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getCallBack(): ?int
    {
        return $this->call_back;
    }

    public function setCallBack(?int $call_back): self
    {
        $this->call_back = $call_back;

        return $this;
    }

    public function getFinancing(): ?int
    {
        return $this->financing;
    }

    public function setFinancing(?int $financing): self
    {
        $this->financing = $financing;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getFinancialOptions(): ?string
    {
        return $this->financial_options;
    }

    public function setFinancialOptions(?string $financial_options): self
    {
        $this->financial_options = $financial_options;

        return $this;
    }

    public function getValidTo(): ?\DateTimeInterface
    {
        return $this->valid_to;
    }

    public function setValidTo(?\DateTimeInterface $valid_to): self
    {
        $this->valid_to = $valid_to;

        return $this;
    }

    /**
     * Gets triggered only on insert

     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        if($this->createdAt == null) {
            $this->setCreatedAt(new \DateTime());
        }

        $validTo = new \DateTime();
        $validTo->modify('+ 8 days');

        $this->valid_to = $validTo;
    }

    public function getStock(): ?Stock
    {
        return $this->stock;
    }

    public function setStock(?Stock $stock): self
    {
        $this->stock = $stock;

        return $this;
    }

}
