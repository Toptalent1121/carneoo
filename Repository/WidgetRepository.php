<?php

namespace App\Repository;

use App\Entity\Page;
use App\Entity\Widget;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Symfony\Bridge\Doctrine\RegistryInterface;
use App\Traits\Panel\RepositoryTrait;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

/**
 * @method Widget|null find($id, $lockMode = null, $lockVersion = null)
 * @method Widget|null findOneBy(array $criteria, array $orderBy = null)
 * @method Widget[]    findAll()
 * @method Widget[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WidgetRepository extends ServiceEntityRepository
{

    use RepositoryTrait;

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Widget::class);
    }

    /**
     * This function returnes all Widget records without banners attached
     * @return ArrayCollection Collection of records is returned
     */
    public function getWidgetsWithoutBanner()
    {
        $em  = $this->getEntityManager();
        $rsm = new ResultSetMappingBuilder($em, ResultSetMappingBuilder::COLUMN_RENAMING_INCREMENT);
        $rsm->addRootEntityFromClassMetadata('App\Entity\Widget', 'w1_');
        $rsm->addJoinedEntityFromClassMetadata('App\Entity\Banner', 'b0_',
            'w1_', 'banner', array('b0_.id' => 'banner_id'));

        $query = $em->createNativeQuery('SELECT ' . $rsm->generateSelectClause() . '
                            FROM widget w1_
                            LEFT JOIN banner b0_ on b0_.widget_id = w1_.id
                            WHERE b0_.id IS NULL', $rsm);
        return $query->getResult();
    }

    /**
     * @param Page $page
     * @return mixed
     */
    public function getPageWidgets(Page $page)
    {
        $queryBuilder = $this->createQueryBuilder('w');

        $queryBuilder->where('w.page = :page');
        $queryBuilder->setParameter('page', $page);

        $queryBuilder->orderBy('w.widget_order', 'ASC');

        return $queryBuilder
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Page $page
     * @return mixed
     */
    public function getPageActiveWidgets(Page $page)
    {
        $queryBuilder = $this->createQueryBuilder('w');

        $queryBuilder->where('w.page = :page');
        $queryBuilder->setParameter('page', $page);
        $queryBuilder->andWhere('w.active = 1');

        $queryBuilder->orderBy('w.widget_order', 'ASC');

        return $queryBuilder
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array
     */
    public function getBannersUsed()
    {
        $queryBuilder = $this->createQueryBuilder('w');
        $queryBuilder->leftJoin('w.banner', 'b');
        $queryBuilder->select('b.id as id');

        $queryBuilder->where('w.banner IS NOT NULL');

        $queryBuilder->orderBy('w.widget_order', 'ASC');

        $result = $queryBuilder
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_ARRAY);

        return array_column($result, "id");
    }
}