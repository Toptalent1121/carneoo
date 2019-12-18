<?php

namespace App\Repository;

use App\Entity\Admin;
use App\Traits\Panel\RepositoryTrait;
use App\Traits\Panel\DatatableRepositoryTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Admin|null find($id, $lockMode = null, $lockVersion = null)
 * @method Admin|null findOneBy(array $criteria, array $orderBy = null)
 * @method Admin[]    findAll()
 * @method Admin[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AdminRepository extends ServiceEntityRepository
{

    use RepositoryTrait,
        DatatableRepositoryTrait;
    
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Admin::class);
    }

    /**
     * @string|int $id
     * @string $role
     */
    public function userHasRole($id, $role)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select('a')
            ->from('App\Entity\Admin', 'a')
            ->where('a.id = :user')
            ->andWhere('a.roles LIKE :roles')
            ->setParameter('user', $id)
            ->setParameter('roles', '%"'.$role.'"%');

        $user = $qb->getQuery()->getResult();

        if (count($user) >= 1) {
            return true;
        } else {
            return false;
        }
    }    
}