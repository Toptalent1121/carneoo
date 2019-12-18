<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\WidgetRepository")
 */
class Widget
{

    use TimestampableEntity;
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Page", inversedBy="widgets")
     * @ORM\JoinColumn(name="page_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $page;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $type;

    protected static $TYPE_CATEGORY = array(
        'BANNER' => 'Banner',
        'SLIDER' => 'Slider',
        'CAR_SEARCH' => 'Car search',
        'HTML' => 'HTML',
    );

    /**
     * @ORM\Column(type="string", nullable=true, length=128)
     */
    private $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $content;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $content_id;

    /**
     * @ORM\Column(type="integer")
     */
    private $widget_order;

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
     * @ORM\Column(type="boolean")
     */
    private $active;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Banner", cascade={"persist"})
     * @ORM\JoinColumn(name="banner", referencedColumnName="id", onDelete="SET NULL")
     */
    private $banner;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPage(): ?Page
    {
        return $this->page;
    }

    public function setPage(?Page $page): self
    {
        $this->page = $page;

        return $this;
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getContentId(): ?int
    {
        return $this->content_id;
    }

    public function setContentId(?int $contentId): self
    {
        $this->content_id = $contentId;

        return $this;
    }

    public function getWidgetOrder(): ?int
    {
        return $this->widget_order;
    }

    public function setWidgetOrder(int $widget_order): self
    {
        $this->widget_order = $widget_order;

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

    public function getAllTypeCategory()
    {
        return self::$TYPE_CATEGORY;
    }

    public function getTypeCategoryFromIndex($i)
    {
        return self::$TYPE_CATEGORY[$i];
    }

    public function getTypeCategory()
    {
        return self::$TYPE_CATEGORY[$this->getType()];
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

    public function getBanner(): ?Banner
    {
        return $this->banner;
    }

    public function setBanner(?Banner $banner): self
    {
        $this->banner = $banner;

        return $this;
    }
}