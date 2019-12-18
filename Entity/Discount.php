<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use InvalidArgumentException;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DiscountRepository")
 */
class Discount
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
     * @ORM\Column(type="text")
     */
    private $name;
	
	/**
     * @ORM\Column(type="text")
     */
    private $front_name;
	
	/**
     * @ORM\Column(type="array")
     */
    private $groups;
    protected static $GROUP_CATEGORY = array(
        'P' => 'Private',
        'F' => 'Firm',
        'D' => 'Disabled'
    );

    /**
     * @ORM\Column(type="string", length=1)
     */
    private $amount_type;
    protected static $AMOUNT_TYPE_CATEGORY = array(
        'Q' => 'Quota',
        'P' => 'Percent'
    );

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $level;
    protected static $LEVEL_CATEGORY = array(
        'MARK' => 'Mark',
        'MODEL' => 'Model',
		'BODY' => 'Model + Karosserie',
        'VERSION' => 'Version',
    );

    /**
     * @ORM\Column(type="float")
     */
    private $value;
	
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Dealer", inversedBy="discounts")
     * @ORM\JoinColumn(name="dealer_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    private $dealer;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $mark;
	
	/**
     * @ORM\Column(type="text", nullable=true)
     */
    private $model;
	
	/**
     * @ORM\Column(type="text", nullable=true)
     */
    private $version;

    /**
     * @ORM\Column(type="boolean")
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
     * @ORM\ManyToOne(targetEntity="App\Entity\Discount", inversedBy="exclusions")
	 * @ORM\JoinColumn(name="discount_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $discount;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Discount", mappedBy="discount")
     */
    private $exclusions;

    /**
     * @ORM\Column(type="float")
     */
    private $carneo_provision;
	
	/**
     * @ORM\Column(type="string", length=1)
     */
    private $carneo_amount_type;
    protected static $CARNEO_AMOUNT_TYPE_CATEGORY = array(
        'Q' => 'Quota',
        'P' => 'Percent'
    );

    /**
     * @ORM\Column(type="string", length=1)
     */
    private $type;
	protected static $TYPE_CATEGORY = array(
        'R' => 'Rabatt',
        'C' => 'Kost'
    );

    /**
     * @ORM\Column(type="boolean")
     */
	private $main;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $obligatory;

    /**
     * @ORM\Column(type="string", length=60, nullable=true)
     */
    private $delivery_time;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $comment;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $body;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Discount", inversedBy="discounts")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Discount", mappedBy="parent")
     */
    private $discounts;

    /**
     * @ORM\Column(type="boolean")
     */
    private $archive;
    

    public function __construct()
    {
        $this->dealers = new ArrayCollection();
        $this->exclusions = new ArrayCollection();
        $this->discounts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
	
	public function getName(): ?string
                                     {
                                 		return $this->name;
                                     }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }
	
	public function getFrontName(): ?string
                                     {
                                 		return $this->front_name;
                                     }

    public function setFrontName(?string $front_name): self
    {
        $this->front_name = $front_name;

        return $this;
    }

    public function getAmountType(): ?string
    {
        return $this->amount_type;
    }

    public function setAmountType(string $amountType): self
    {
        if (!array_key_exists($amountType, self::$AMOUNT_TYPE_CATEGORY)) {
            throw new InvalidArgumentException('Unrecognized Discount Amount Type Category named: '.$amountType);
        }
        $this->amount_type = $amountType;

        return $this;
    }
	
	public function getCarneoAmountType(): ?string
                                     {
                                 		return $this->carneo_amount_type;
                                     }

    public function setCarneoAmountType(string $carneoAmountType): self
    {
        if (!array_key_exists($carneoAmountType, self::$CARNEO_AMOUNT_TYPE_CATEGORY)) {
            throw new InvalidArgumentException('Unrecognized Discount Amount Type Category named: '.$carneoAmountType);
        }
        $this->carneo_amount_type = $carneoAmountType;

        return $this;
    }
	
	public function getGroups(): ?array
                                     {
                                 		return $this->groups;
                                     }

    public function setGroups(array $groups): self
    {
        $this->groups = $groups;

        return $this;
    }

    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function setLevel(string $level): self
    {
        if (!array_key_exists($level, self::$LEVEL_CATEGORY)) {
            throw new InvalidArgumentException('Unrecognized Discount Level Category named: '.$level);
        }

        $this->level = $level;

        return $this;
    }

    public function getValue(): ?float
    {
        return $this->value;
    }

    public function setValue(float $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
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

    public function getAllCostTypeCategory()
    {
        return self::$COST_TYPE_CATEGORY;
    }

    public function getAllType()
    {
        return self::$TYPE_CATEGORY;
    }
	
	public function searchCostType(?string $search)
                                     {
                                 		foreach($this->getAllCostTypeCategory() as $key => $value)
                                 		{
                                 			if (stripos($value, $search) !== false) {
                                 				return $key;
                                 			}
                                 		}		
                                 		return false;
                                     }

    public function searchType(?string $search)
    {
        foreach($this->getAllType() as $key => $value)
        {
            if (stripos($value, $search) !== false) {
                return $key;
            }
        }
        return false;
    }
	
	public function getAllGroupCategory()
                                     {
                                 		return self::$GROUP_CATEGORY;
                                     }

    public function getAllAmountTypeCategory()
    {
        return self::$AMOUNT_TYPE_CATEGORY;
    }

    public function getAmountTypeCategory(?string $amountType = null)
    {
        if (empty($amountType)) {
            return self::$AMOUNT_TYPE_CATEGORY[$this->getAmountType()];
        }

        if (!array_key_exists($amountType, self::$AMOUNT_TYPE_CATEGORY)) {
            throw new InvalidArgumentException('Unrecognized Discount Amount Type Category named: '.$amountType);
        }

        return self::$AMOUNT_TYPE_CATEGORY[$amountType];
    }
	
	public function getCarneoAmountTypeCategory(?string $carneoAmountType = null)
                                                                {
                                                                    if (empty($carneoAmountType)) {
                                                                        return self::$CARNEO_AMOUNT_TYPE_CATEGORY[$this->getCarneoAmountType()];
                                                                    }
                                                            
                                                                    if (!array_key_exists($carneoAmountType, self::$CARNEO_AMOUNT_TYPE_CATEGORY)) {
                                                                        throw new InvalidArgumentException('Unrecognized Discount Amount Type Category named: '.$carneoAmountType);
                                                                    }
                                                            
                                                                    return self::$AMOUNT_TYPE_CATEGORY[$amountType];
                                                                }

    public function getAllLavelCategory()
    {
        return self::$LEVEL_CATEGORY;
    }

    public function getLevelCategory(?string $level = null)
    {
        if (empty($level)) {
            return self::$LEVEL_CATEGORY[$this->getLevel()];
        }

        if (!array_key_exists($level, self::$LEVEL_CATEGORY)) {
            throw new InvalidArgumentException('Unrecognized Discount Level Category named: '.$level);
        }

        return self::$LEVEL_CATEGORY[$level];
    }

    public function setDealer(Dealer $dealer) {
        $this->dealer = $dealer;
        return $this;
    }

    public function getDealer() {
        return $this->dealer;
    }

    public function getDiscount(): ?Discount
    {
        return $this->discount;
    }

    public function setDiscount(?Discount $discount): self
    {
        $this->discount = $discount;

        return $this;
    }

    public function getExclusions(): ?Collection
    {
        return $this->exclusions;
    }

    /**
     * @param \App\Entity\Discount|null $exclusion
     * @return \self
     */
    public function addExclusion(Discount $exclusion): self
    {
        $this->exclusions[] = $exclusion;
        $exclusion->setDiscount($this);

        return $this;
    }

    public function removeExclusion(Discount $exclusion): self
    {
        if ($this->exclusions->contains($exclusion)) {
            $this->exclusions->removeElement($exclusion);
            $exclusion->setDiscount(null);
        }

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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getMain(): ?bool
    {
        return $this->main;
    }

    public function setMain(bool $main): self
    {
        $this->main = $main;

        return $this;
    }

    public function getObligatory(): ?bool
    {
        return $this->obligatory;
    }

    public function setObligatory(bool $obligatory): self
    {
        $this->obligatory = $obligatory;

        return $this;
    }
	
	public function getAllTypeCategory()
                                                                {
                                                                    return self::$TYPE_CATEGORY;
                                                                }

    public function getTypeCategory(?string $type = null)
    {
        if (empty($type)) {
            return self::$TYPE_CATEGORY[$this->getType()];
        }

        if (!array_key_exists($type, self::$TYPE_CATEGORY)) {
            throw new InvalidArgumentException('Unrecognized Discount Level Category named: '.$type);
        }

        return self::$TYPE_CATEGORY[$type];
    }

    public function getDeliveryTime(): ?string
    {
        return $this->delivery_time;
    }

    public function setDeliveryTime(?string $delivery_time): self
    {
        $this->delivery_time = $delivery_time;

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

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getDiscounts(): Collection
    {
        return $this->discounts;
    }

    public function addDiscount(self $discount): self
    {
        if (!$this->discounts->contains($discount)) {
            $this->discounts[] = $discount;
            $discount->setParent($this);
        }

        return $this;
    }

    public function removeDiscount(self $discount): self
    {
        if ($this->discounts->contains($discount)) {
            $this->discounts->removeElement($discount);
            // set the owning side to null (unless already changed)
            if ($discount->getParent() === $this) {
                $discount->setParent(null);
            }
        }

        return $this;
    }

    public function getArchive(): ?bool
    {
        return $this->archive;
    }

    public function setArchive(?bool $archive): self
    {
        $this->archive = $archive;

        return $this;
    }
}