<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Symfony\Bridge\Doctrine\RegistryInterface;
use App\Traits\Panel\RepositoryTrait;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    use RepositoryTrait;
	
	public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @param string $email
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByEmail(string $email)
    {
        $queryBuilder = $this->createQueryBuilder('u');

        $queryBuilder->where('u.email like :email');
        $queryBuilder->andWhere('u.password IS NOT NULL');
        $queryBuilder->setParameter('email', $email);

        $queryBuilder->setMaxResults(1);

        return $queryBuilder
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string $token
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByToken(string $token)
    {
        $queryBuilder = $this->createQueryBuilder('u');

        $queryBuilder->where('u.password IS NOT NULL');

        $queryBuilder->having('sha1(u.email) = :token');
        $queryBuilder->setParameter('token', $token);

        $queryBuilder->setMaxResults(1);

        return $queryBuilder
            ->getQuery()
            ->getOneOrNullResult();
    }
}
