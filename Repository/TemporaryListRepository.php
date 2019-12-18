<?php

namespace App\Repository;

use App\Entity\TemporaryList;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use App\Traits\Panel\DBALTrait;

/**
 * @method TemporaryList|null find($id, $lockMode = null, $lockVersion = null)
 * @method TemporaryList|null findOneBy(array $criteria, array $orderBy = null)
 * @method TemporaryList[]    findAll()
 * @method TemporaryList[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TemporaryListRepository extends ServiceEntityRepository
{
    use DBALTrait;
	
	public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TemporaryList::class);
    }

    /**
     * @return mixed
     */
    public function getDiscountsForSlider()
    {
        $queryBuilder = $this->createQueryBuilder('tl');

        $queryBuilder
			->select('tl.id, tl.mark, tl.model, tl.body, tl.version, tl.price, tl.discount_max, tl.active_from, tl.active_to, tl.image, (tl.price-tl.price*tl.discount_max/100) as sprice')
			->where('tl.image is not NULL')
			->orderBy('tl.discount_max', 'DESC')
			->groupBy('tl.model')
			->setMaxResults(5);

        return $queryBuilder
            ->getQuery()
            ->getResult();
    }
	
	/**
     * @return mixed
     */
    public function getCheapestForSlider()
    {
        $queryBuilder = $this->createQueryBuilder('tl');

        $queryBuilder
			->select('tl.id, tl.mark, tl.model, tl.body, tl.version, tl.price, tl.discount_max, tl.active_from, tl.active_to, tl.image, (tl.price-tl.price*tl.discount_max/100) as sprice')
			->where('tl.image is not NULL')
			->orderBy('sprice', 'ASC')
			->setMaxResults(5);

        return $queryBuilder
            ->getQuery()
            ->getResult();
    }
	
	/**
     * @param $page
     * @param $sort
     * @param $dir
	 * @param $limit
     * @param $filter
     *
     * @return array
     */
    public function getAllDiscounts($type, $page, $sort, $dir, $limit, $filter)
    {
        $connection = $this->getConnection();
		
		$subquery = 'SELECT sub.*,(sub.price-sub.price*sub.discount_max/100) as sprice FROM temporary_list sub WHERE sub.image is not NULL';
        
        if($filter != null) {
            foreach($filter as $field => $values) {
                $orQuery = '(';
                foreach($values as $value) {
                    $orQuery .= 'sub.' . $field.' LIKE \''.$value.'%\' OR ';
                }
                $orQuery = substr($orQuery,0,-3);
                $orQuery .= ')';
                $subquery .= ' AND '.$orQuery;
            }
        }
		
		//ORDERS		
		if($type == 'discount'){
			$subquery .= ' GROUP BY sub.model ';
			$subquery .= ' ORDER BY sub.discount_max DESC';
		}
		if($type == 'price'){
			$subquery .= ' ORDER BY sprice ASC';
		}
		
		$subquery .= ' LIMIT 10';
		$query = 'SELECT tl.* FROM ('.$subquery.') AS tl';
		
		if($sort == 'mark') {
            $query .= ' ORDER BY tl.mark '.$dir.',tl.model '.$dir;
        } else {
            $query .= ' ORDER BY '.($sort == 'sprice' ? $sort : 'tl.'.$sort).' '.$dir;
        }
		
		//$query .= ' LIMIT '.($limit * ($page - 1)).','.$limit;
		
        return array(
			'result' => $connection->fetchAll($query),
			'count' => count($connection->fetchAll($subquery))
		);
    }
	
	/**
     * @return mixed
     */
    public function getActiveMarks($bestseller = false, $order = false)
    {
		$connection = $this->getConnection();
		
		$query = 'SELECT tl.mark AS name,ml.logo AS logo,ml.url AS url FROM temporary_list tl LEFT JOIN mark_logo ml ON tl.mark = ml.mark WHERE ml.active = 1';
		
		if($bestseller == true){
			
			$query .= ' AND ml.bestseller = 1';
		}
		
		$query .= ' GROUP BY tl.mark';
		
		if($order == true){
			
			$query .= ' ORDER BY ml.sort ASC';
		}
		
		return $connection->fetchAll($query);        
    }
}
