<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use App\Traits\Panel\RepositoryTrait;
use App\Entity\Widget;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PageRepository")
 */
class Page
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
     * @ORM\ManyToOne(targetEntity="App\Entity\Page", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="Page", mappedBy="parent")
     * @ORM\OrderBy({"page_order" = "ASC"})
     */
    protected $children;

    /**
     * @ORM\Column(type="string", length=128)
     */
    private $name;

    /**
     * @Gedmo\Slug(fields={"name"})
     * @ORM\Column(type="string", length=128)
     */
    private $slug;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Widget", mappedBy="page")
     * @ORM\OrderBy({"widget_order" = "ASC"})
     */
    public $widgets;

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
    private $menu;

    /**
     * @ORM\Column(type="boolean")
     */
    private $active;

    /**
     * @ORM\Column(type="integer")
     */
    private $page_order;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $home_page;

    public function __construct()
    {
        $this->widgets  = new ArrayCollection();
        $this->children = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParent(): ?Page
    {
        return $this->parent;
    }

    public function setParent(?Page $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getChildren(): ?Collection
    {
        return $this->children;
    }

    /**
     * Sets child and parent for child at the same time
     * @param \App\Entity\Page|null $child
     * @return \self
     */
    public function addChild(Page $child): self
    {
        $this->children[] = $child;
        $child->setParent($this);

        return $this;
    }

    public function removeChild(Page $child): self
    {
        if ($this->children->contains($child)) {
            $this->children->removeElement($child);
            $child->setParent(null);
        }

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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return Collection|Widget[]
     */
    public function getWidgets(): Collection
    {
        return $this->widgets;
    }

    public function addWidget(Widget $widget): self
    {
        if (!$this->widgets->contains($widget)) {
            $this->widgets[] = $widget;
            $widget->setPage($this);
        }

        return $this;
    }

    public function removeWidget(Widget $widget): self
    {
        if ($this->widgets->contains($widget)) {
            $this->widgets->removeElement($widget);
            // set the owning side to null (unless already changed)
            if ($widget->getPage() === $this) {
                $widget->setPage(null);
            }
        }

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

    public function getMenu(): ?bool
    {
        return $this->menu;
    }

    public function setMenu(bool $menu): self
    {
        $this->menu = $menu;

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

    public function getPageOrder(): ?int
    {
        return $this->page_order;
    }

    public function setPageOrder(int $page_order): self
    {
        $this->page_order = $page_order;

        return $this;
    }

    public function getHomePage(): ?bool
    {
        return $this->home_page;
    }

    public function setHomePage(?bool $home_page): self
    {
        $this->home_page = $home_page;

        return $this;
    }
}