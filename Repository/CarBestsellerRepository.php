<?php

namespace App\Repository;

use App\Entity\CarBestseller;
use App\Traits\Panel\DatatableRepositoryTrait;
use App\Traits\Panel\RepositoryTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method CarBestseller|null find($id, $lockMode = null, $lockVersion = null)
 * @method CarBestseller|null findOneBy(array $criteria, array $orderBy = null)
 * @method CarBestseller[]    findAll()
 * @method CarBestseller[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CarBestsellerRepository extends ServiceEntityRepository
{
    use RepositoryTrait,
        DatatableRepositoryTrait;

    /**
     * CarBestsellerRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CarBestseller::class);
    }

    /**
     * @return mixed
     *
     * @throws \Exception
     */
    public function getAll()
    {
        $queryBuilder = $this->createQueryBuilder('cb');

        $queryBuilder->orderBy('cb.car_bestseller_order', 'ASC');

        return $queryBuilder
            ->getQuery()
            ->getResult();
    }

    /**
     * @return mixed
     *
     * @throws \Exception
     */
    public function getAllActive()
    {
        $queryBuilder = $this->createQueryBuilder('cb');

        $queryBuilder->where('cb.active = 1');

        $queryBuilder->orderBy('cb.car_bestseller_order', 'ASC');

        return $queryBuilder
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $page
     * @param $sort
     * @param $dir
     * @param $filter
     *
     * @return Paginator
     */
    public function getAllCarBestSellers($page, $sort, $dir, $filter)
    {
        $queryBuilder = $this->createQueryBuilder('cb');
        if($sort == 'mark') {
            $queryBuilder->orderBy('cb.mark', $dir);
            $queryBuilder->orderBy('cb.model', $dir);
        } else {
            $queryBuilder->orderBy('cb.' . $sort, $dir);
        }

        if($filter != null) {
            foreach($filter as $field => $values) {
                $orQuery = '(';
                foreach($values as $value) {
                    $orQuery .= $field.' LIKE \''.$value.'%\' OR ';
                }
                $orQuery = substr($orQuery,0,-3);
                $orQuery .= ')';
                $queryBuilder->andWhere($orQuery);
            }
        }

        $query = $queryBuilder->getQuery();

        $paginator = $this->paginate($query, $page);

        return $paginator;
    }

    /**
     * @param Doctrine\ORM\Query $dql   DQL Query Object
     * @param integer            $page  Current page (defaults to 1)
     * @param integer            $limit The total number per page (defaults to 5)
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function paginate($dql, $page = 1, $limit = 5)
    {
        $paginator = new Paginator($dql);

        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1)) // Offset
            ->setMaxResults($limit); // Limit

        return $paginator;
    }
}
