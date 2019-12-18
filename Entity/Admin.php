<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use App\Traits\Panel\RepositoryTrait;
use App\Entity\Role;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AdminRepository")
 */
class Admin implements UserInterface, \Serializable
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
     * @ORM\Column(type="string", length=64)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $last_name;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=256)
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    private $phone;

    /**
     * @ORM\Column(type="array")
     */
    private $roles = [];

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Role", inversedBy="users")
     * @ORM\JoinColumn(name="role_id", referencedColumnName="id")
     */
    private $role;

    /**
     * @ORM\Column(type="boolean")
     */
    private $active = 0;

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

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(?string $last_name): self
    {
        $this->last_name = $last_name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(?bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return array Role
     */
    public function getRoles()
    {
        $roles = $this->roles;
        return array_unique($roles);
    }

    /**
     * @param array $roles
     * @return Role
     */
    public function setRoles($roles = null)
    {
        $this->roles = $roles;

        return $this;
    }

    public function getUsername()
    {
        return $this->getEmail();
    }

    public function eraseCredentials()
    {
        
    }

    /** @see \Serializable::serialize() */
    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->email,
            $this->password,
        ));
    }

    /**
     * @return string
     */
    public function getSalt()
    {
        return;
    }

    /**
     * @param $salt
     * @return Account
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }

    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->email,
            $this->password,
            ) = unserialize($serialized, array('allowed_classes' => false));
    }

    /**
     * @return Collection|Role[]
     */
    public function getRole(): ?Role
    {
        return $this->role;
    }

    /**
     * @return Collection|Role[]
     */
    public function setRole(Role $role): self
    {
        $this->role = $role;
        return $this;
    }
}