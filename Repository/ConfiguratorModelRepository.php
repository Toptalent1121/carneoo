<?php

namespace App\Repository;

use App\Repository\GenericRepository;
use App\Traits\Panel\JATOImportMapperTrait;
use Doctrine\DBAL\FetchMode;
use App\Traits\Panel\JATOMapperTrait;

/**
 * Class ConfiguratorMarkRepository
 * @package App\Repository
 */
class ConfiguratorModelRepository extends GenericRepository
{
	use JATOMapperTrait;
	
	/**
     * @return array
     */
    public function getModels($start, $length, $orders, $search, $columns)
    {
        $queryBuilder = $this->getConnection()->createQueryBuilder();
        $queryBuilder
            ->select('v.'.self::$JATO_VERSION_MAPPER['vehicle_id'].' AS vehicle_id', 'v.'.self::$JATO_VERSION_MAPPER['mark'].' AS mark', 'v.'.self::$JATO_VERSION_MAPPER['model_slug'].' AS model_slug', 'v.'.self::$JATO_VERSION_MAPPER['descriptor'].' AS descriptor', 'v.'.self::$JATO_VERSION_MAPPER['version'].' AS version', 'v.'.self::$JATO_VERSION_MAPPER['price'].' AS price')
            ->from('version', 'v')
			->where('v.'.self::$JATO_VERSION_MAPPER['version_slug'].' NOT LIKE \'(O)%\'');

        if (!empty($search['value'])) {
            $queryBuilder->andWhere('v.'.self::$JATO_VERSION_MAPPER['mark'].' LIKE \'%'.$search['value'].'\'');
            $queryBuilder->orWhere('v.'.self::$JATO_VERSION_MAPPER['model_slug'].' LIKE \'%'.$search['value'].'\'');
            $queryBuilder->orWhere('v.'.self::$JATO_VERSION_MAPPER['descriptor'].' \'%'.$search['value'].'\'');
            $queryBuilder->orWhere('v.'.self::$JATO_VERSION_MAPPER['version'].' LIKE \'%'.$search['value'].'\'');
        }

        $individualSearchConditions = $this->getIndividialColumnSearch($columns);

        if (!empty($individualSearchConditions)) {
            foreach ($individualSearchConditions as $condition) {
                $queryBuilder->andWhere($condition);
            }
        }

        $count = $queryBuilder->execute()->rowCount();

        $queryBuilder->setFirstResult($start)->setMaxResults($length);
        if (!empty($orders)) {
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

    private function mapColumn(string $columnName, bool $inverse = false)
    {
        $mapDefinition = self::$JATO_VERSION_MAPPER;
        if($inverse == true) {
            $mapDefinition = array_flip($mapDefinition);
        }

        $returnMappedColumn = function($mapDefinition) use ($columnName) {
            if (!array_key_exists($columnName, $mapDefinition)) {
                return "";
            }

            return $mapDefinition[$columnName];
        };

        if (!$inverse) {
            return $returnMappedColumn($mapDefinition);
        }

        $flipped = array_flip($mapDefinition);
        return $returnMappedColumn($flipped);
    }

    /**
     * Applies individual columns search conditions to the query based on Datatable data
     * @param array $columns Collection of columns definition
     * @return array Collection of LIKE conditions is returned beased on individual column search
     */
    protected function getIndividialColumnSearch(array $columns)
    {
        $searchQuery = [];
        foreach ($columns as $column) {
            if (trim($column['search']['value']) === '') {
                continue;
            }

            if (substr($column['name'], 0, 1) === '_') {
                continue;
            }
            $columnName    = $this->mapColumn($column['name'], true);
            $searchItem    = trim($column['search']['value']);
            $searchQuery[] = 'v.'.$columnName.' LIKE \'%'.$searchItem.'%\'';
        }

        return $searchQuery;
    }

    /**
     * @return array
     */
    public function getAllModels() 
	{	
		$queryBuilder = $this->getConnection()->createQueryBuilder();
		$queryBuilder
			->select('v.'.self::$JATO_VERSION_MAPPER['vehicle_id'].' AS model_id, v.'.self::$JATO_VERSION_MAPPER['model_slug'].' AS name')
			->from('version','v')
			->where('v.'.self::$JATO_VERSION_MAPPER['version_slug'].' NOT LIKE \'(O)%\'');
		
		return $queryBuilder->execute();
    }

    /**
     * @return array
     */
    public function getModel($model_id)
    {
        $queryBuilder = $this->getConnection()->createQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from('version', 'v')
            ->where('v.' .self::$JATO_VERSION_MAPPER['model_slug']. ' LIKE :model')
			->andWhere('v.'.self::$JATO_VERSION_MAPPER['version_slug'].' NOT LIKE \'(O)%\'')
            ->setParameter('model', $model_id);

        return $queryBuilder->execute();
    }
	
	/**
     * @return array
     */
    public function getVersion($vehicle_id)
    {
        $queryBuilder = $this->getConnection()->createQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from('version', 'v')
            ->where('v.' .self::$JATO_VERSION_MAPPER['vehicle_id']. ' = :vehicle_id')
            ->setParameter('vehicle_id', $vehicle_id);

        return $queryBuilder->execute();
    }

    /**
     * @param $vehicle_id
     *
     * @return \Doctrine\DBAL\Driver\Statement|int
     */
    public function getJatoVersion($vehicle_id)
    {
        $queryBuilder = $this->getConnection()->createQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from('version', 'v')
            ->where('v.' .self::$JATO_VERSION_MAPPER['jato_vehicle_id']. ' = :vehicle_id')
            ->setParameter('vehicle_id', $vehicle_id);

        return $queryBuilder->execute();
    }
	
	/**
     * @param string $name
     *
     * @return mixed
     */
    public function findModelVehiclesByName(string $name)
    {
        $result = $this->findModelByName($name);

        if(!$result) {
            $names = explode(' ', $name);
            if (isset($names[1]) && strlen($names[1]) == 1) {
                $modelName = $names[0] . $names[1];
                unset($names[0], $names[1]);
            } else {
                $modelName = $names[0];
                unset($names[0]);
            }

            $result = $this->findModelByName($modelName);

            if(!$result && isset(JATOImportMapperTrait::$JATO_MODEL_MAPPER[$name])) {
                $result = $this->findModelByName(JATOImportMapperTrait::$JATO_MODEL_MAPPER[$name]);
            }
        }

        return $result;
    }
	
	/**
     * @param string $modelName
     *
     * @return mixed[]
     */
    private function findModelByName(string $modelName)
    {
        $queryBuilder = $this->getConnection()->createQueryBuilder();
        $queryBuilder
            ->select('v.' . self::$JATO_VERSION_MAPPER['model_slug'] . ' AS vehicle_id')
            ->from('version','v')
            ->where('v.' . self::$JATO_VERSION_MAPPER['model_name'] . ' LIKE :modelName')
			->andWhere('v.'.self::$JATO_VERSION_MAPPER['version_slug'].' NOT LIKE \'(O)%\'')
            ->setParameters([
                'modelName' => $modelName .'%',
            ]);

        $result = $queryBuilder
            ->execute()
            ->fetch();

        return $result;
    }
	
	//FRONT METHODS
	
	/**
     * @return array
     */
    public function getFilteredModels(array $fields, array $filter=array(), $standardFields=array(), $groupBy=null, $language='DE',$outdated=false) {
		
		$queryBuilder = $this->getConnection()->createQueryBuilder();
		$query = 'ml.logo AS logo,';
		
		if(count($fields)>0){
			foreach($fields as $column)
			{
				if(strstr($column,'M_')){
					$query .= 'MIN(v.'.self::$JATO_VERSION_MAPPER[substr($column,2)].') AS '.substr($column,2).',';
				}else{
					$query .= 'v.'.self::$JATO_VERSION_MAPPER[$column].' AS '.$column.',';
				}
			}			
		}
		
		foreach($filter as $column => $value)
		{
			$negative = false;
			if(strstr($value,'!')){
				$negative = true;
				$value = substr($value,1);
			}
			$queryBuilder->andWhere('v.'.self::$JATO_VERSION_MAPPER[$column].($negative == true ? ' != ' : '=').'\''.$value.'\'');
		}
		
		if(count($standardFields)>0){
			$n=1;
			foreach($standardFields as $field)
			{
				$query .= 'st'.$n.'.content AS '.$field.',';
				$queryBuilder->innerJoin('v', 'standard_text', 'st'.$n, 'v.vehicle_id = st'.$n.'.vehicle_id');
				$queryBuilder->andWhere('st'.$n.'.schema_id = '.self::$JATO_STANDARD_MAPPER[$field]);
				$queryBuilder->andWhere('st'.$n.'.language_id = '.$this->getLanguage($language));
				$n++;
			}
		}
		
		$queryBuilder
			->select(substr($query,0,-1))
			->from('version','v')
			->leftJoin('v', 'mark_logo', 'ml', 'v.'.self::$JATO_VERSION_MAPPER['mark'].' = ml.mark');
		
		if($groupBy != null){
			$queryBuilder->groupBy($groupBy);		
		}
		
		if($outdated == false){
			$queryBuilder->andWhere('v.'.self::$JATO_VERSION_MAPPER['version_slug'].' NOT LIKE \'(O)%\'');
		}

		return $queryBuilder->execute();
    }
	
	/**
     * @return array
     */
    public function getImageByModelAndBody($model,$body) {
		
		$queryBuilder = $this->getConnection()->createQueryBuilder();
		$queryBuilder
			->select('ij.imaca_id')
			->from('imaca_jato','ij')
			->where('ij.model = \''.$model.'\'')
			->andWhere('ij.body = \''.$body.'\'');
		
		return $queryBuilder->execute()->fetchColumn();
    }
	
	/**
     * @return array
     */
    public function getMinPrice(array $filter) {
		
		$queryBuilder = $this->getConnection()->createQueryBuilder();
		$queryBuilder
			->select('MIN(v.'.self::$JATO_VERSION_MAPPER['price'].') AS min_price')
			->from('version','v')
			->where('1=1');
		
		foreach($filter as $column => $value)
		{
			$queryBuilder->andWhere('v.'.self::$JATO_VERSION_MAPPER[$column].' = \''.$value.'\'');
		}
		
		return $queryBuilder->execute();
    }
	
	/**
     * @return array
     */
    public function getMaxPrice(array $filter) {
		
		$queryBuilder = $this->getConnection()->createQueryBuilder();
		$queryBuilder
			->select('MAX(v.'.self::$JATO_VERSION_MAPPER['price'].') AS max_price')
			->from('version','v')
			->where('1=1');
		
		foreach($filter as $column => $value)
		{
			$queryBuilder->andWhere('v.'.self::$JATO_VERSION_MAPPER[$column].' = \''.$value.'\'');
		}
		
		return $queryBuilder->execute();
    }
	
	/**
     * @return array
     */
    public function getMinPower(array $filter) {
		
		$queryBuilder = $this->getConnection()->createQueryBuilder();
		$queryBuilder
			->select('MIN(v.'.self::$JATO_VERSION_MAPPER['power'].') AS min_power')
			->from('version','v')
			->where('1=1');
		
		foreach($filter as $column => $value)
		{
			$queryBuilder->andWhere('v.'.self::$JATO_VERSION_MAPPER[$column].' = \''.$value.'\'');
		}
		
		return $queryBuilder->execute();
    }
	
	/**
     * @return array
     */
    public function getMaxPower(array $filter) {
		
		$queryBuilder = $this->getConnection()->createQueryBuilder();
		$queryBuilder
			->select('MAX(v.'.self::$JATO_VERSION_MAPPER['power'].') AS max_power')
			->from('version','v')
			->where('1=1');
		
		foreach($filter as $column => $value)
		{
			$queryBuilder->andWhere('v.'.self::$JATO_VERSION_MAPPER[$column].' = \''.$value.'\'');
		}
		
		return $queryBuilder->execute();
    }
	
	/**
     * @return array
     */
    public function getEquipment($vehicle_id,$option_type,$schema_id=null,$value=null,$groupBy=null,$category=null,$test=false) 
	{	
		$queryBuilder = $this->getConnection()->createQueryBuilder();
		$queryBuilder
			->select('e.schema_id, e.option_id, e.data_value AS value, ol.manuf_name AS name, ol.option_code AS code, ol.'.self::$JATO_VERSION_MAPPER['price'].' AS price')
			->from('equipment','e')
			->leftJoin('e', 'option_list', 'ol', 'e.option_id = ol.option_id')
			->where('e.vehicle_id = \''.$vehicle_id.'\'')
			->andWhere('ol.vehicle_id = \''.$vehicle_id.'\'')
			->andWhere('e.option_id != 0')
			->andWhere('ol.option_type = \''.$option_type.'\'');
		
		if($schema_id != null){
			$negative = false;
			if(strstr($value,'!')){
				$negative = true;
				$value = substr($value,1);
			}
			$queryBuilder->andWhere('e.schema_id '.($negative == true ? ' != ' : ' = ').$schema_id);
		}
		
		if($value != null){
			$queryBuilder->andWhere('e.data_value = '.$value);
		}
		
		if($groupBy != null){
			$queryBuilder->groupBy($groupBy);
		}
		
		if($category != null){
			$queryBuilder
				->leftJoin('e', 'schema_categories', 'sc', 'e.schema_id = sc.schema_id')
				->andWhere('sc.language_id = '.self::$LANGUAGE['EN'])
				->andWhere('sc.category_name LIKE \'%'.$category.'%\'');
		}
		
		if($test == true)
			die($queryBuilder->getSql());
		
		return $queryBuilder->execute();
    }
	
	/**
     * @return array
     */
    public function getOptionArray($data) 
	{
		$option = array();
		foreach($data as $row)
		{
			$option[] = $row['option_id'];
		}
		return $option;
	}
	/**
     * @return array
     */
    public function getOption($vehicle_id,$option_id) 
	{		
		$queryBuilder = $this->getConnection()->createQueryBuilder();
		$queryBuilder
			->select('ol.option_id AS option_id,ol.manuf_name AS name, ol.option_code AS code, ol.'.self::$JATO_VERSION_MAPPER['price'].' AS price')
			->from('option_list','ol')
			->where('ol.vehicle_id = \''.$vehicle_id.'\'')
			->andWhere('ol.option_id = '.$option_id);
			
		return $queryBuilder->execute()->fetch();
    }
	
	/**
     * @return array
     */
    public function getOptionByType($vehicle_id,$optionType) 
	{		
		$queryBuilder = $this->getConnection()->createQueryBuilder();
		$queryBuilder
			->select('ol.option_id, ol.manuf_name AS name, ol.option_code AS code, ol.'.self::$JATO_VERSION_MAPPER['price'].' AS price')
			->from('option_list','ol')
			->where('ol.vehicle_id = \''.$vehicle_id.'\'')
			->andWhere('ol.option_type = \''.$optionType.'\'');
			
		return $queryBuilder->execute();
    }
	
	/**
     * @return array
     */
    public function getOptionValue($vehicle_id,$option_id,$field) 
	{		
		$queryBuilder = $this->getConnection()->createQueryBuilder();
		$queryBuilder
			->select('e.data_value AS value')
			->from('equipment','e')
			->where('e.vehicle_id = \''.$vehicle_id.'\'')
			->andWhere('e.option_id = '.$option_id)
			->andWhere('e.schema_id = '.self::$JATO_STANDARD_MAPPER[$field]);
			
		return $queryBuilder->execute()->fetchColumn();
    }
	
	/**
     * @return array
     */
    public function getOptionDescription($vehicle_id,$option_id,$language='DE') 
	{		
		$queryBuilder = $this->getConnection()->createQueryBuilder();
		$queryBuilder
			->select('ot.content AS description')
			->from('option_text','ot')
			->where('ot.vehicle_id = \''.$vehicle_id.'\'')
			->andWhere('ot.option_id = '.$option_id)
			->andWhere('ot.language_id = '.$this->getLanguage($language));
			
		return $queryBuilder->execute();
    }
	
	/**
     * @return array
     */
    public function getSchemaDescription($schema_id,$value,$language='DE',$short=false) 
	{		
		$queryBuilder = $this->getConnection()->createQueryBuilder();
		
		if($short == false){
			$queryBuilder->select('st.full_text AS description');
		}else{
			$queryBuilder->select('st.abbr_text AS description');
		}
		
		$queryBuilder
			->from('schema_text','st')
			->where('st.data_value = \''.$value.'\'')
			->andWhere('st.schema_id = '.$schema_id)
			->andWhere('st.language_id = '.$this->getLanguage($language));
			
		return $queryBuilder->execute()->fetchColumn();
    }
	
	/**
     * @return array
     */
    public function getStandardList($vehicle_id,$language='DE') 
	{		
		$queryBuilder = $this->getConnection()->createQueryBuilder();
		$queryBuilder
			->select('st.content AS description')
            ->addSelect('st.schema_id as schema_id')
			->from('standard_text','st')
			->where('st.vehicle_id = '.$vehicle_id)
			->andWhere('st.language_id = '.$this->getLanguage($language));
			
		return $queryBuilder->execute();
    }
	
	/**
     * @return array
     */
    public function getStandardDescription($field,$vehicle_id,$language='DE') 
	{		
		$queryBuilder = $this->getConnection()->createQueryBuilder();
		$queryBuilder
			->select('st.content AS description')
			->from('standard_text','st')
			->where('st.vehicle_id = '.$vehicle_id)
			->andWhere('st.schema_id = '.self::$JATO_STANDARD_MAPPER[$field])
			->andWhere('st.language_id = '.$this->getLanguage($language));
			
		return $queryBuilder->execute()->fetchColumn();
    }
	
	/**
     * @return array
     */
    public function getOptionBuild($vehicle_id,$option_id,$rule) 
	{		
		$queryBuilder = $this->getConnection()->createQueryBuilder();
		$queryBuilder
			->select('*')
			->from('option_build','ob')
			->where('ob.vehicle_id = \''.$vehicle_id.'\'')
			->andWhere('ob.option_id = '.$option_id)
			->andWhere('ob.rule_type = '.$rule);
		
		//die($queryBuilder->getSql());
		
		return $queryBuilder->execute();
    }
}