<?php

namespace App\Repository;

use App\Entity\Discount;
use App\Traits\Panel\RepositoryTrait;
use App\Traits\Panel\DatatableRepositoryTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Traits\Panel\JATOMapperTrait;


/**
 * @method Discount|null find($id, $lockMode = null, $lockVersion = null)
 * @method Discount|null findOneBy(array $criteria, array $orderBy = null)
 * @method Discount[]    findAll()
 * @method Discount[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DiscountRepository extends ServiceEntityRepository
{
    use RepositoryTrait, DatatableRepositoryTrait;
	use JATOMapperTrait;
	
	public function __construct(RegistryInterface $registry, EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, Discount::class);
		$this->entityManager = $entityManager;
    }
	
	protected function parseSearch($column, $searchItem)
    {
		$entity = new \App\Entity\Discount;
		
		if($column == 'type')
		{
			$searchItem = $entity->searchType($searchItem);
		}
		elseif($column == 'version')
		{
			$queryBuilder = $this->entityManager->getConnection()->createQueryBuilder();
			$queryBuilder
				->select('DISTINCT v.'.self::$JATO_VERSION_MAPPER['vehicle_id'].' AS vehicle_id')
				->from('version','v')
				->where('v.'.self::$JATO_VERSION_MAPPER['version'].' LIKE \'%'.$searchItem.'%\'');
			
			$vehicles = $queryBuilder->execute()->fetchAll();
			if($vehicles) {
				foreach($vehicles as $vehicle)
				{
					$search[] = $vehicle['vehicle_id'];
				}
				$searchItem = array_unique($search);
			}
		}
		
		return $searchItem;	
	}

    /**
     * @param $start
     * @param $length
     * @param $orders
     * @param $search
     * @param $columns
     * @param $otherConditions
     *
     * @return array
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getRequiredDTData($start, $length, $orders, $search, $columns, $otherConditions)
    {

        $this->query       = $this->createQueryBuilder($this->entityAlias);
        $this->countQuery  = $this->createQueryBuilder($this->entityAlias);
        $this->countQuery->select('COUNT('.$this->entityAlias.')');
        $this->columnNames = array_flip($this->getClassMetadata()->getColumnNames());

        if ($otherConditions === null) {
            $this->query->where("1=1");
            $this->countQuery->where("1=1");
        } else {
            $this->query->where($otherConditions);
            $this->countQuery->where($otherConditions);
        }

        $this->query->andWhere($this->entityAlias . '.parent is null');
        $this->countQuery->andWhere($this->entityAlias . '.parent is null');
		
		$this->query->andWhere($this->entityAlias . '.archive = 0');
        $this->countQuery->andWhere($this->entityAlias . '.archive = 0');
		
        $this->getIndividialColumnSearch($columns);
        $this->getGlobalColumnsSearch($columns, $search);

        $this->query->setFirstResult($start)
            ->setMaxResults($length);

        $this->getOrder($orders);

        $results     = $this->query->getQuery()->getResult();
        $countResult = $this->countQuery->getQuery()->getSingleScalarResult();

        return array(
            "results" => $results,
            "countResult" => $countResult
        );
    }
	
	/**
     * @param array $filter
	 * @param string $scale
	 * @param bool $main
	 *
     * @return mixed
     */
    public function findDiscountRange(array $vehicles, string $scale, array $filter = array(), $group = null, $one = true, $groupBy = null, $test = false)
    {
        $dir= array('MAX' => 'DESC','MIN' => 'ASC');
		
		$queryBuilder = $this->createQueryBuilder('d');
		$queryBuilder
			->where('d.active = 1')
			->andWhere('d.archive = 0')
			->andWhere('d.mark = :mark')			
			->andWhere('d.parent is not null')
			->andWhere('d.level = :level')
			->setParameter('mark', $vehicles['mark'])			
			->orderBy('d.value', $dir[$scale]);
			
		if($one == true){
			$queryBuilder
				->select('d.id,d.value,d.carneo_provision as provision,d.active_from,d.active_to')
				->setMaxResults(1);
		}
		
		if(isset($vehicles['version'])){
			$queryBuilder
				->andWhere('d.version = :version')
				->setParameter('version', $vehicles['version'])
				->setParameter('level', 'VERSION');
		}elseif(isset($vehicles['body'])){
			$queryBuilder
				->andWhere('d.body = :body')
				->andWhere('d.model = :model')
				->setParameter('body', $vehicles['body'])
				->setParameter('model', $vehicles['model'])
				->setParameter('level', 'BODY');	
		}elseif(isset($vehicles['model'])){
			$queryBuilder
				->andWhere('d.model = :model')
				->setParameter('model', $vehicles['model'])
				->setParameter('level', 'MODEL');	
		}else{
			$queryBuilder->setParameter('level', 'MARK');
		}
		
		foreach($filter as $column => $value)
		{
			$negative = false; 
			if(strstr($value,'!')){
				$negative = true;
				$value = substr($value,1);
			}
			$queryBuilder->andWhere('d.'.$column.($negative == true ? ' != ' : '=').'\''.$value.'\'');
		}
		
		if($group != null){
			$queryBuilder->andWhere($queryBuilder->expr()->like('d.groups', $queryBuilder->expr()->literal("%$group%")));
		}
		
		if($groupBy != null){
			$queryBuilder->groupBy($groupBy);
		}
		
		if($test == true){
			die($queryBuilder->getQuery()->getSQL().' '.print_r($queryBuilder->getQuery()->getParameters()));
		}
		
        $result = $queryBuilder
            ->getQuery()
            ->getResult();
		
		if($result)
			if($one == true){
				return $result[0];
			}else{
				return $result;
			}
		else
			return false;
    }
	
	/**
     * @return mixed
     */
    public function findAllChildrenByLevel($level)
    {
        $queryBuilder = $this->createQueryBuilder('d');

        $queryBuilder
			->where('d.parent is not null')
			->andWhere('d.archive = 0')
			->andWhere('d.level = :level')
			->setParameter('level', $level);

        return $queryBuilder
            ->getQuery()
            ->getResult();
    }

    /**
     * @return mixed
     */
    public function findWithOutParent()
    {
        $queryBuilder = $this->createQueryBuilder('d');

        $queryBuilder->where('d.parent is null');

        return $queryBuilder
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Discount $parent
     * @param string $model
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findChildWithModel(Discount $parent, string $model)
    {
        $queryBuilder = $this->createQueryBuilder('d');

        $queryBuilder->where('d.parent = :parent');
        $queryBuilder->andWhere('d.model LIKE :model');
        $queryBuilder->andWhere('d.archive = 0');

        $queryBuilder->setParameter('parent', $parent);
        $queryBuilder->setParameter('model', $model);

        return $queryBuilder
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param Discount $parent
     * @param string $body
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findChildWithBody(Discount $parent, string $body)
    {
        $queryBuilder = $this->createQueryBuilder('d');

        $queryBuilder->where('d.parent = :parent');
        $queryBuilder->andWhere('d.body LIKE :body');
        $queryBuilder->andWhere('d.archive = 0');

        $queryBuilder->setParameter('parent', $parent);
        $queryBuilder->setParameter('body', $body);

        return $queryBuilder
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param Discount $parent
     * @param string $version
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findChildWithVersion(Discount $parent, string $version)
    {
        $queryBuilder = $this->createQueryBuilder('d');

        $queryBuilder->where('d.parent = :parent');
        $queryBuilder->andWhere('d.version LIKE :version');
        $queryBuilder->andWhere('d.archive = 0');

        $queryBuilder->setParameter('parent', $parent);
        $queryBuilder->setParameter('version', $version);

        return $queryBuilder
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param Discount $parent
     * @return mixed
     */
    public function findByParent(Discount $parent)
    {
        $queryBuilder = $this->createQueryBuilder('d');

        $queryBuilder->where('d.parent = :parent');
        $queryBuilder->setParameter('parent', $parent);
        $queryBuilder->andWhere('d.archive = 0');

        return $queryBuilder
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Discount $parent
     *
     * @return array
     */
    public function getChildrenByLevel(Discount $parent)
    {
        $queryBuilder = $this->createQueryBuilder('d');

        switch ($parent->getLevel()) {
            case 'MODEL':
                $queryBuilder->select('d.model as id');
                break;
            case 'BODY':
                $queryBuilder->select('d.body as id');
                break;
            case 'VERSION':
                $queryBuilder->select('d.version as id');
                break;
        }

        $queryBuilder->where('d.parent = :parent');
        $queryBuilder->andWhere('d.archive = 0');
        $queryBuilder->setParameter('parent', $parent);

        $result = $queryBuilder
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_ARRAY);

        return array_column($result, "id");
    }
}
