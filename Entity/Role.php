<?php

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use App\Traits\Panel\RepositoryTrait;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RoleRepository")
 */
class Role
{

    use TimestampableEntity,
        RepositoryTrait;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }
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
     * @ORM\Column(type="boolean")
     */
    private $primary_role;

    /**
     * @ORM\Column(type="text")
     */
    private $permissions;

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
     * One role has many users. This is the inverse side.
     * @ORM\OneToMany(targetEntity="App\Entity\Admin", mappedBy="role")
     */
    private $users;

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

    public function getPrimaryRole(): ?bool
    {
        return $this->primary_role;
    }

    public function setPrimaryRole(bool $primary): self
    {
        $this->primary_role = $primary;

        return $this;
    }

    /**
     *
     * @param bool|null $raw Permissions are in JSON string. If oyu want to get row string set this parameter to true. FALSE is set by default
     * @return string|null
     */
    public function getPermissions(?bool $raw = false)
    {
        if ($raw) {
            return $this->permissions;
        }

        $permissions = json_decode($this->permissions, true);
        return $permissions;
    }

    /**
     *
     * @param mixed $permissions if array is provided it will be converted to JSON string. If string is provided
     * @return \self
     */
    public function setPermissions($permissions): self
    {
        if (is_array($permissions)) {
            $this->permissions = json_encode($permissions);
            return $this;
        }

        $this->permissions = $permissions;
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

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getUsers()
    {
        return $this->users;
    }

    public function addUser(Admin $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->setRole($this);
        }

        return $this;
    }

    public function removeUser(Admin $user): self
    {
        if ($this->users->contains($user)) {
            $this->users->removeElement($user);
            // set the owning side to null (unless already changed)
            if ($user->getRole() === $this) {
                $user->setRole(null);
            }
        }

        return $this;
    }
}