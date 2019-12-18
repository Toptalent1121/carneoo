<?php

namespace App\Repository;

use App\Repository\GenericRepository;
use App\Traits\Panel\JATOMapperTrait;
use Doctrine\DBAL\FetchMode;

/**
 * Class ConfiguratorMarkRepository
 * @package App\Repository
 */
class ConfiguratorMarkRepository extends GenericRepository
{
    use JATOMapperTrait;

    /**
     * @return array
     */
    public function getMarks($start=null, $length=null, $orders=null, $search=null, $bestseller = null, $active = null) {
		
		$queryBuilder = $this->getConnection()->createQueryBuilder();
		$queryBuilder
			->select('v.'.self::$JATO_VERSION_MAPPER['mark'].' AS name','ml.logo AS logo','ml.url AS url','ml.active AS active')
			->from('version','v')
			->leftJoin('v', 'mark_logo', 'ml', 'v.'.self::$JATO_VERSION_MAPPER['mark'].' = ml.mark')
			->where('1=1')
			->groupBy('name');
		
		if($bestseller !== null){
			$queryBuilder->andWhere('ml.bestseller = :bestseller')->setParameter('bestseller', $bestseller);
		}
		
		if($active !== null){
			$queryBuilder->andWhere('ml.active = :active')->setParameter('active', $active);
		}
		
		if($search !== null && !empty($search['value'])) {
				$queryBuilder->andWhere('v.'.self::$JATO_VERSION_MAPPER['mark'].' LIKE \'%'.$search['value'].'\'');
        }
		
		$count = $queryBuilder->execute()->rowCount();
		
		if($start !== null && $length!= null) {
			$queryBuilder->setFirstResult($start)->setMaxResults($length);
		}
		
		if($orders != null) {
			foreach ($orders as $order) {
				// $order['name'] is the name of the order column as sent by the JS
				if (trim($order['name']) === '') {
					continue;
				}

				if (substr($order['name'], 0, 1) === '_') {
					continue;
				}

				$queryBuilder->orderBy($order['name'], $order['dir']);
			}
		}
		
		return array(
            "records" => $queryBuilder->execute(),
            "countRecords" => $count
        );
    }

    /**
     * @return array
     */
    public function getAllMarks() {
		
		$queryBuilder = $this->getConnection()->createQueryBuilder();
		$queryBuilder
			->select('v.id_101 AS vehicle_id, v.'.self::$JATO_VERSION_MAPPER['mark'].' AS name')
			->from('version','v')
			->groupBy('v.id_111');
		
		return $queryBuilder->execute();
    }
	
	/**
     * @return array
     */
    public function getMark($mark) {
		
		$queryBuilder = $this->getConnection()->createQueryBuilder();
		$queryBuilder
			->select('v.' . self::$JATO_VERSION_MAPPER['mark'] . ' AS mark','ml.logo AS logo','ml.url AS url','ml.active AS active')
			->from('version','v')
			->leftJoin('v', 'mark_logo', 'ml', 'v.'.self::$JATO_VERSION_MAPPER['mark'].' = ml.mark')
			->where('v.' . self::$JATO_VERSION_MAPPER['mark'] . ' LIKE :mark')
            ->setParameter('mark', $mark)
			->groupBy('mark');
		
		return $queryBuilder->execute();
    }

    /**
     * @param $model
     *
     * @return mixed
     */
    public function getMarkFromModel($model) {

        $queryBuilder = $this->getConnection()->createQueryBuilder();
        $queryBuilder
            ->select('v.' . self::$JATO_VERSION_MAPPER['mark'] . ' AS mark')
            ->from('version','v')
            ->where('v.' . self::$JATO_VERSION_MAPPER['model_slug'] . ' LIKE :model')
            ->setParameter('model', $model)
        ;

        return $queryBuilder->execute()->fetch();
    }
	
	/**
     * @param $model
     *
     * @return mixed
     */
    public function getMarkFromVersion($vehicle_id) {

        $queryBuilder = $this->getConnection()->createQueryBuilder();
        $queryBuilder
            ->select('v.' . self::$JATO_VERSION_MAPPER['mark'] . ' AS mark')
            ->from('version','v')
            ->where('v.' . self::$JATO_VERSION_MAPPER['vehicle_id'] . ' = :vehicle_id')
            ->setParameter('vehicle_id', $vehicle_id)
        ;

        return $queryBuilder->execute()->fetch();
    }
	
	public function updateMark($mark_id,$data) {
		
		$queryBuilder = $this->getConnection()->createQueryBuilder();
		$queryBuilder
			->update('mark_logo')
			->set('logo', '\''.$data['logo'].'\'')
			->set('url', '\''.$data['url'].'\'')
			->where('mark = \''.$mark_id.'\'');
		
		return $queryBuilder->execute();
	}
	
	public function changeStatus($mark_id,$status) {
		
		$queryBuilder = $this->getConnection()->createQueryBuilder();
		$queryBuilder
			->update('mark_logo')
			->set('active', $status)
			->where('mark = \''.$mark_id.'\'');
		
		return $queryBuilder->execute();
	}

    /**
     * @param string $name
     *
     * @return mixed
     */
	public function getMarkUrlByName($name)
    {
        $queryBuilder = $this->getConnection()->createQueryBuilder();

        $queryBuilder->select('m.url');
        $queryBuilder->from('mark_logo', 'm');

        $queryBuilder->where('m.mark LIKE :name');
        $queryBuilder->setParameter('name', $name);

        return $queryBuilder->execute()->fetch();
    }
}