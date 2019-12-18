<?php

namespace App\Repository;

use App\Entity\Role;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use App\Traits\Panel\RepositoryTrait;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @method Role|null find($id, $lockMode = null, $lockVersion = null)
 * @method Role|null findOneBy(array $criteria, array $orderBy = null)
 * @method Role[]    findAll()
 * @method Role[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RoleRepository extends ServiceEntityRepository
{

    use RepositoryTrait;
    protected $token;

    public function __construct(RegistryInterface $registry, TokenStorageInterface $token)
    {
        parent::__construct($registry, Role::class);
        $this->token      = $token;
    }

    public function getFilteredRoles()
    {
        $qb = $this->createQueryBuilder('r');
        $qb->where('r.active = :active')
            ->setParameter('active', true);

        return $qb->getQuery()
                ->getResult();
    }
}