<?php

namespace App\Repository;

use App\Entity\Offer;
use App\Traits\Panel\DatatableRepositoryTrait;
use App\Traits\Panel\RepositoryTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Offer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Offer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Offer[]    findAll()
 * @method Offer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OfferRepository extends ServiceEntityRepository
{
    use RepositoryTrait,
        DatatableRepositoryTrait;

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Offer::class);
    }
}
