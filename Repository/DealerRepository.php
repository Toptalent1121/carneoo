<?php

namespace App\Repository;

use App\Entity\Dealer;
use App\Traits\Panel\RepositoryTrait;
use App\Traits\Panel\DatatableRepositoryTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Dealer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Dealer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Dealer[]    findAll()
 * @method Dealer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DealerRepository extends ServiceEntityRepository
{

    use RepositoryTrait, DatatableRepositoryTrait;

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Dealer::class);
    }
}