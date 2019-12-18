<?php

namespace App\Security\Panel;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use App\Entity\Admin;
use App\Security\Panel\UniversalVoterException;

class UniversalVoter extends Voter
{
    protected $userPermissions = [];
    protected $user;
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    protected function supports($attribute, $subject)
    {
        return true;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $this->user = $token->getUser();

        if (!$this->user instanceof Admin) {
            return false;
        }

        $userRole   = $this->user->getRole();

        if (empty($userRole)) {
            throw new UniversalVoterException('Unable to get User Role. Role has not been assigned to the user');
        }

        if (!$userRole->getActive()) {
            return false;
        }

        //check for promaty status before permission validation
        //role with promaty flag has always full access rights
        if ($userRole->getPrimaryRole()) {
            return true;
        }

        if (!array_key_exists(strtolower($attribute), $this->getPermissions())) {
            return false;
        }

        return $this->checkPermission($subject, $attribute);
    }

    /**
     * Checkes if user is owner of current entity
     * @param mixed $entity Form Entity
     * @param Admin $loggedInUser Currently logged in user
     * @return boolean if false is returned is not the ownet of the resource. True for otherwise
     */
    protected function isOwner($entity = null)
    {
        if (empty($entity)) {
            return false;
        }

        //if admin check ids
        if ($entity instanceof Admin) {
            if ($entity->getId() !== $this->user->getId()) {
                return false;
            }
            return true;
        }

        //if other entity
        if (!method_exists($entity, 'getCreatedBy')) {
            return false;
        }

        if ($entity->getCreatedBy()->getId() !== $this->user->getId()) {
            return false;
        }

        return true;
    }

    /**
     * Checkes is user has permission for given resource
     * @param mixed $entity entity instance
     * @param string $permissionName Permission name ie. users_create
     * @return boolean True is returned is has permission, false otherwise
     */
    protected function checkPermission($entity, $permissionName)
    {
        if ($entity) {
            if ($this->isOwner($entity)) {
                return true;
            }
        }

        if ($this->getPermissions()[$permissionName]) {
            return true;
        }

        return false;
    }

    /**
     * Returnes definition of all user permissions base don user's role
     * @return array Collection of user permissions is returned
     * @throws UniversalVoterException
     */
    protected function getPermissions()
    {
        if (!empty($this->userPermissions)) {
            return $this->userPermissions;
        }

        $permissionsDefinition = $this->container->getParameter('permissions');
        $userRole              = $this->user->getRole();
        $collectedPermissions  = [];

        if (empty($userRole)) {
            throw new UniversalVoterException('Unable to get User Role. Role has not been assigned to the user');
        }

        $rolePermissions = $userRole->getPermissions();

        if (empty($permissionsDefinition)) {
            throw new UniversalVoterException('Unable to get collection of Permissions');
        }

        if (empty($rolePermissions)) {
            throw new UniversalVoterException('Empty permissions definition for user role. Please verify role configuration');
        }

        foreach ($permissionsDefinition as $permissionDef => $permissionDefValue) {
            if (!isset($rolePermissions[$permissionDef])) {
                $collectedPermissions[$permissionDef] = (bool) $permissionDefValue;
                continue;
            }

            $collectedPermissions[$permissionDef] = (bool) $rolePermissions[$permissionDef];
        }

        $this->userPermissions = $collectedPermissions;
        return $collectedPermissions;
    }
}