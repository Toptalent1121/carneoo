<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use App\Traits\Panel\RepositoryTrait;
use App\Entity\Widget;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BannerRepository")
 */
class Banner
{

    use TimestampableEntity,
        RepositoryTrait;
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
     * @ORM\Column(type="string", length=128)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $filename;

    /**
     * @ORM\Column(type="boolean")
     */
    private $active;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $alt;

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

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;

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

    public function getAlt(): ?string
    {
        return $this->alt;
    }

    public function setAlt(?string $alt): self
    {
        $this->alt = $alt;

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
}