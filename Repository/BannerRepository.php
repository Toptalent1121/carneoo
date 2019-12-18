<?php

namespace App\Repository;

use App\Entity\Banner;
use App\Traits\Panel\RepositoryTrait;
use App\Traits\Panel\DatatableRepositoryTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Banner|null find($id, $lockMode = null, $lockVersion = null)
 * @method Banner|null findOneBy(array $criteria, array $orderBy = null)
 * @method Banner[]    findAll()
 * @method Banner[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BannerRepository extends ServiceEntityRepository
{

    use RepositoryTrait,
        DatatableRepositoryTrait;

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Banner::class);
    }
}