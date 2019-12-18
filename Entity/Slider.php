<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SliderRepository")
 */
class Slider
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
    private $name;

    /**
     * @ORM\Column(type="boolean")
     */
    private $active;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\SliderItem", mappedBy="slider", orphanRemoval=true)
     */
    private $sliderItems;

    public function __construct()
    {
        $this->sliderItems = new ArrayCollection();
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

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return Collection|SliderItem[]
     */
    public function getSliderItems(): Collection
    {
        return $this->sliderItems;
    }

    public function addSliderItem(SliderItem $sliderItem): self
    {
        if (!$this->sliderItems->contains($sliderItem)) {
            $this->sliderItems[] = $sliderItem;
            $sliderItem->setSlider($this);
        }

        return $this;
    }

    public function removeSliderItem(SliderItem $sliderItem): self
    {
        if ($this->sliderItems->contains($sliderItem)) {
            $this->sliderItems->removeElement($sliderItem);
            // set the owning side to null (unless already changed)
            if ($sliderItem->getSlider() === $this) {
                $sliderItem->setSlider(null);
            }
        }

        return $this;
    }
}
