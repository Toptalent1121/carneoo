<?php

namespace App\Repository;

use App\Entity\Stock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use App\Traits\Panel\DBALTrait;

/**
 * @method Stock|null find($id, $lockMode = null, $lockVersion = null)
 * @method Stock|null findOneBy(array $criteria, array $orderBy = null)
 * @method Stock[]    findAll()
 * @method Stock[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StockRepository extends ServiceEntityRepository
{
    use DBALTrait;
	
	public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Stock::class);
    }

    /**
     * @return mixed
     */
    public function getStockCarsForSlider()
    {
        $queryBuilder = $this->createQueryBuilder('s')
			->where('s.image IS NOT NULL')
			->orderBy('s.price', 'ASC')
			->setMaxResults(5);

        return $queryBuilder
            ->getQuery()
            ->getResult();
    }
	
	/**
	 * @param string $mark
	 * @param string $model
     * @return mixed
     */
    public function getStockCarsByModel($mark, $model)
    {
        $queryBuilder = $this->createQueryBuilder('s')
			->where('s.mark LIKE \''.$mark.'\'')
			->andWhere('s.name LIKE \'%'.$model.'%\'')
			->orderBy('s.price', 'ASC');

        return $queryBuilder
            ->getQuery()
            ->getResult();
    }

    /**
	* @param integer $page
	* @param string $sort
	* @param string $dir
	* @param int $limit
	* @param array $filter
	*
	* @return array
	*/
	public function getAllStock($page, $sort, $dir, $limit, $filter)
	{	
		$connection = $this->getConnection();
		
		$subquery = 'SELECT sub.* FROM stock AS sub WHERE sub.archive = 0 AND sub.image IS NOT NULL LIMIT 50';
		
		$query = 'SELECT s.*,(s.price-s.price*s.discount/100) as sprice FROM ('.$subquery.') AS s WHERE 1=1';
		
		if($filter != null){
			
			if(isset($filter['power_to'])){
				
				if(!isset($filter['power_from']))
					$filter['power_from'] = 0;
				
				$query .= ' AND power BETWEEN '.$filter['power_from'][0].' AND '.$filter['power_to'][0];
				unset($filter['power_from']);
				unset($filter['power_to']);
			}
			
			foreach($filter as $field => $values)
			{
				$orQuery = '(';
				foreach($values as $value)
				{					
					if($field == 'name'){
						$value = trim(strstr($value,' '));
					}
					
					$orQuery .= 's.'.$field.' LIKE \''.$value.'%\' OR ';
				}
				$orQuery = substr($orQuery,0,-3);
				$orQuery .= ')';
				$query .= ' AND '.$orQuery;
			}
		}
		$query .= ' ORDER BY '.($sort == 'sprice' ? $sort : 's.'.$sort).' '.$dir;
		
		$count = count($connection->fetchAll($query));
		
		$query .= ' LIMIT '.($limit * ($page - 1)).','.$limit;
		//die(var_dump($query));
		
        return array(
			'result' => $connection->fetchAll($query),
			'count' => $count
		);
	}
}
