<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MailQueueRepository")
 * @ORM\HasLifecycleCallbacks
 */
class MailQueue
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
    private $type;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Offer")
     */
    private $Offer;

    /**
     * @ORM\Column(type="datetime",)
     */
    private $created_at;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $send_at;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getOffer(): ?Offer
    {
        return $this->Offer;
    }

    public function setOffer(?Offer $Offer): self
    {
        $this->Offer = $Offer;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getSendAt(): ?\DateTimeInterface
    {
        return $this->send_at;
    }

    public function setSendAt(?\DateTimeInterface $send_at): self
    {
        $this->send_at = $send_at;

        return $this;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updatedTimestamps(): void
    {
        if ($this->getCreatedAt() === null) {
            $this->setCreatedAt(new \DateTime('now'));
        }
    }
}
