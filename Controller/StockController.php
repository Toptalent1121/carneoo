<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Stock;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class StockController
 *
 * @package App\Controller
 */
class StockController extends AbstractController
{

    /**
     * @var EntityManagerInterface $em
     */
    private $em;

    /**
     * StockController constructor.
     *
     * @param EntityManagerInterface $em
     */
	public function __construct(EntityManagerInterface $em)
    {
		$this->em = $em;
    }
	
	/**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index()
    {
		return $this->render('stock/index.html.twig');
    }

    /**
     * @param ajax $request
	 *
     * @return \Symfony\Component\HttpFoundation\JsonResponse;
     */
    public function list(Request $request)
    {
		
		$data = $request->request->all();
		
		if($data['filter'] != 'false'){
			$filter = array();
			foreach($data['filter'] as $filterArray)
			{
				if($filterArray['value'])
					$filter[$filterArray['name']][] = $filterArray['value'];
			}
		}else{
			$filter = null;
		}
		
		if(!isset($data['page'])){
			$page = 1;
		}else{
			$page = $data['page'];
		}			
		

		if($data['sort'] != ''){
			$sort = $data['sort'];
			$dir = $data['dir'];
		}else{
			$sort = 'name';
			$dir = 'asc';
		}
		
		if($data['per_page'] == 'alle'){
			$limit = 50;
		}else{
			$limit = $data['per_page'];
		}
		
		$stockList = $this->em->getRepository(Stock::class)->getAllStock($page,$sort,$dir,$limit,$filter);
		
		$maxPages = ceil($stockList['count'] / $limit);
		
		$modelArray = array();
		$model[]['value'] = '';
		$bodyArray = array();
		$colorArray = array();
		$fuelArray = array();
		$gearArray = array();
		$powerArray = array();
		$driveArray = array();
		$body = array();
        $color = array();
        $fuel = array();
        $gear = array();
        $power = array();
        $drive = array();

		foreach($stockList['result'] as $item)
		{		
			$nameArray = explode(' ',$item['name']);
			$name = $nameArray[0].' '.$nameArray[1];
			if(!in_array($name,$modelArray) && $name != ''){
				$model[]['value'] = $item['mark'].' '.$name;
				$modelArray[] = $name;
			}			
			if(!in_array($item['body'],$bodyArray) && $item['body'] != ''){
				$body[]['value'] = $bodyArray[] = $item['body'];
			}			
			if(!in_array($item['color'],$colorArray) && $item['color'] != ''){
				$color[]['value'] = $colorArray[] = $item['color'];
			}
			if(!in_array($item['fuel'],$fuelArray) && $item['fuel'] != ''){
				$fuel[]['value'] = $fuelArray[] = $item['fuel'];
			}
			if(!in_array($item['gear'],$gearArray) && $item['gear'] != ''){
				$gear[]['value'] = $gearArray[] = $item['gear'];
			}
			//if(!in_array($item['power'],$powerArray) && $item['power'] != ''){
			//	$power[]['value'] = $powerArray[] = $item['power'];
			//}
			if(!in_array($item['drive'],$driveArray) && $item['drive'] != ''){
				$drive[]['value'] = $driveArray[] = $item['drive'];
			}
		}
		
		sort($model);
		$filterList = array(
			array(
				'name' => 'Marke und Modell',
				'type' => 'select',
				'type_name' => 'name',
				'option_list' => $model
			),
			array(
				'name' => 'Aufbauart',
				'type' => 'checkbox',
				'type_name' => 'body',
				'option_list' => $body
			),
			array(
				'name' => 'Farbe',
				'type' => 'checkbox',
				'type_name' => 'color',
				'option_list' => $color
			),
			array(
				'name' => 'Treibstoff',
				'type' => 'checkbox',
				'type_name' => 'fuel',
				'option_list' => $fuel
			),
			array(
				'name' => 'Getriebe',
				'type' => 'checkbox',
				'type_name' => 'gear',
				'option_list' => $gear
			),
			array(
				'name' => 'Leistung',
				'type' => 'range',
				'type_name' => 'power',
				'option_list' => $power
			),
			array(
				'name' => 'Antriebsart',
				'type' => 'checkbox',
				'type_name' => 'drive',
				'option_list' => $drive
			)
		);
		
		$result = $this->render('stock/list.html.twig',
		[
			'list' => $stockList['result'],
			'filterList' => $filterList,
			'maxPages' => $maxPages,
			'page' => $page,
			'filter' => $filter
		])->getContent();
		
		return new JsonResponse($result);
    }

    /**
	 * @param integer $id
	 *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function details(int $id)
    {
        $car = $this->em->getRepository(Stock::class)->find($id);

		return $this->render('stock/details.html.twig',
		[
			'car' => $car
		]);
    }
	
	/**
	 * @param string $mark
	 * @param string $model
	 * @param string $cabine
	 *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function configuratorList(string $mark, string $model, string $cabine)
    {
        $cars = $this->em->getRepository(Stock::class)->getStockCarsByModel($mark, $model);
		
		$carList = array();
		$n = 1;
		foreach($cars as $car)
		{
			$carList[] = array(
				'id' => $n,
				'src' =>'/uploads/cars/'.$car->getImage().'.png',
				'alt' => $car->getName(),
				'name' => $car->getMark(),
				'model' => $car->getName(),
				'more' => false,
				'popUp' => true,
				'href' => '/stock/details/'.$car->getId()
			);
		}
		
		if(count($carList) == 0){
			return $this->redirectToRoute('configurator_group');
		}
		
		return $this->render('stock/configurator_list.html.twig',
		[
			'mark' => $mark,
			'model' => $model,
			'cabine' => $cabine,
			'car_list' => $carList
		]);
    }
}