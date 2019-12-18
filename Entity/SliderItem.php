<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SliderItemRepository")
 */
class SliderItem
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=70)
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=70, nullable=true)
     */
    private $sub_title;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $filename;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Slider", inversedBy="sliderItems")
     * @ORM\JoinColumn(nullable=false)
     */
    private $slider;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getSubTitle(): ?string
    {
        return $this->sub_title;
    }

    public function setSubTitle(?string $sub_title): self
    {
        $this->sub_title = $sub_title;

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

    public function getSlider(): ?Slider
    {
        return $this->slider;
    }

    public function setSlider(?Slider $slider): self
    {
        $this->slider = $slider;

        return $this;
    }
}
