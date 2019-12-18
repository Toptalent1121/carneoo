<?php

namespace App\Controller;

use App\Entity\CarBestseller;
use App\Entity\Stock;
use App\Repository\ConfiguratorModelRepository;
use App\Repository\DiscountRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CarBestsellerController
 *
 * @package App\Controller
 */
class CarBestsellerController extends AbstractController
{
    /**
     * @var EntityManagerInterface $em
     */
    private $em;

    /**
     * CarBestsellerController constructor.
     *
     * @param EntityManagerInterface $em
     */
	public function __construct(EntityManagerInterface $em, ConfiguratorModelRepository $configuratorModelRepository, DiscountRepository $discountRepository)
    {
		$this->em = $em;
		$this->configuratorModelRepository = $configuratorModelRepository;
		$this->discountRepository = $discountRepository;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index()
    {
        return $this->render('car_bestseller/index.html.twig');
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function list(Request $request)
    {
        $data = $request->request->all();
		
        $filter = [];
		/*
        if($data['filter'] != 'false') {
            foreach($data['filter'] as $filterArray) {
                $filter[$filterArray['name']][] = $filterArray['value'];
            }
        } else {
            $filter = null;
        }
		*/
        if(!isset($data['page'])) {
            $page = 1;
        } else {
            $page = $data['page'];
        }
		

        if($data['sort'] != '') {
            $sort = $data['sort'];
            $dir = $data['dir'];
        } else {
            $sort = 'mark';
            $dir = 'asc';
        }

        $carBestsellersList = $this->em->getRepository(CarBestseller::class)->getAllCarBestSellers($page, $sort, $dir, $filter);
        $limit = 10;
        $maxPages = ceil($carBestsellersList->count() / $limit);
		
        $carBestsellersList = $this->prepareCarBestsellerData($carBestsellersList);
        $carBestsellersListAll = $this->em->getRepository(CarBestseller::class)->findAll();
	
		/*
        $markArray = array();
        $modelArray = array();
        $model = array();
        $model[]['value'] = '';
        $mark = array();
        $mark[]['value'] = '';

        foreach($carBestsellersListAll as $item) {
            /**
             * @var CarBestseller $item
             */
           /* if(!in_array($item->getMark(), $markArray) && $item->getMark() != ''){
                $mark[]['value'] = $markArray[] = $item->getMark();
            }
            if(!in_array($item->getModel(), $modelArray) && $item->getModel() != ''){
                $model[]['value'] = $modelArray[] = $item->getModel();
            }
        }

        $filterList = array(
            array(
                'name' => 'Marke',
                'type' => 'select',
                'type_name' => 'cb.mark',
                'option_list' => $mark
            ),
            array(
                'name' => 'Modell',
                'type' => 'select',
                'type_name' => 'cb.model',
                'option_list' => $model
            ),
        );
		*/
        $result = $this->render('car_bestseller/list.html.twig',
            [
                'list' => $carBestsellersList,
            //    'filterList' => $filterList,
                'maxPages' => $maxPages,
                'page' => $page,
            //    'filter' => $filter
            ])->getContent();

        return new JsonResponse($result);
    }

    /**
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function details($id)
    {
        $carBestseller = $this->em->getRepository(CarBestseller::class)->find($id);

        if($carBestseller->getVersion()) {
            $criteria = ['model_slug' => $carBestseller->getModel(),'vehicle_id'=>$carBestseller->getVersion()];
        } else {
            $criteria = ['model_slug' => $carBestseller->getModel()];
        }

        $fields = [
            'mark','jato_vehicle_id', 'model_slug', 'cabine', 'power', 'gear', 'fuel', 'drive', 'doors', 'engine',
        ];
        $standardFields = [
            'consumption', 'energy_class',
        ];
        $car = $this->configuratorModelRepository->getFilteredModels($fields ,$criteria, $standardFields,'cabine')->fetch();
        $standardOptions = $this->configuratorModelRepository->getStandardList($car['jato_vehicle_id']);
		
		$discount = $this->discountRepository->findDiscountRange(array('mark' => $carBestseller->getMark(), 'model' => $carBestseller->getModel()),'MAX',array('amount_type' => 'P','main' => true));
		$carneooPrice = $carBestseller->getMinPrice() - $carBestseller->getMinPrice()*($discount['value']-$discount['provision'])/100;

        $options = [];
        foreach($standardOptions as $standardOption) {
            if(false == in_array($standardOption['schema_id'], $this->configuratorModelRepository::$STANDARD_OPTIONS_EXCLUSION_SCHEMA_ID)) {
                $options[] = $standardOption['description'];
            }
        }

        $car['capacity'] = $this->configuratorModelRepository->getOptionValue($car['jato_vehicle_id'],0,'capacity');
        $car['weight_1'] = $this->configuratorModelRepository->getOptionValue($car['jato_vehicle_id'],0,'weight_1');
        $car['weight_2'] = $this->configuratorModelRepository->getOptionValue($car['jato_vehicle_id'],0,'weight_2');
        $car['drive'] = $this->configuratorModelRepository->getSchemaDescription($this->configuratorModelRepository::$JATO_STANDARD_MAPPER['drive'], $car['drive']);
        $car['gear'] = $this->configuratorModelRepository->getSchemaDescription($this->configuratorModelRepository::$JATO_STANDARD_MAPPER['gear'], $car['gear']);
        $car['fuel'] = $this->configuratorModelRepository->getSchemaDescription($this->configuratorModelRepository::$JATO_STANDARD_MAPPER['fuel'], $car['fuel']);
        $car['weight'] = $this->configuratorModelRepository->getOptionValue($car['jato_vehicle_id'],0,'weight_1');
        $car['energy_class'] = substr($car['energy_class'],23);
        $car['consumption'] = [
            $this->configuratorModelRepository->getOptionValue($car['jato_vehicle_id'],0,'consumption_city'),
            $this->configuratorModelRepository->getOptionValue($car['jato_vehicle_id'],0,'consumption_country'),
            $this->configuratorModelRepository->getOptionValue($car['jato_vehicle_id'],0,'consumption_average'),
        ];
		$image = $this->configuratorModelRepository->getImageByModelAndBody($car['model_slug'],$car['cabine']);

        return $this->render('car_bestseller/details.html.twig',
            [
                'carBestseller' => $carBestseller,
                'car' => $car,
                'options' => $options,
                'image' => '/uploads/cars/'.$image.'.png',
                'price' => $carneooPrice,
                'discount' => ($discount['value']-$discount['provision']),
            ]);
    }

    /**
     * @param $carBestsellers
     * 
     * @return array
     * @throws \Exception
     */
    private function prepareCarBestsellerData($carBestsellers): array
    {
        $data = [];

        if($carBestsellers) {
            foreach ($carBestsellers as $carBestseller) {
                
				$data[] = $this->prepareCarData($carBestseller);
            }
        }

        return $data;
    }

    /**
     * @param $carBestseller
     * 
     * @return array
     * @throws \Exception
     */
    private function prepareCarData($carBestseller): array
    {
        /**
         * @var CarBestseller $carBestseller
         */
        if($carBestseller->getVersion()) {
            $criteria = ['model_slug' => $carBestseller->getModel(),'vehicle_id'=>$carBestseller->getVersion()];
        } else {
            $criteria = ['model_slug' => $carBestseller->getModel()];
        }
        
        $fields = [
            'jato_vehicle_id', 'model_slug', 'cabine', 'power', 'gear', 'fuel', 'doors', 'engine', 'drive'
        ];
        $car = $this->configuratorModelRepository->getFilteredModels($fields, $criteria, [],'cabine')->fetch();
		$image = $this->configuratorModelRepository->getImageByModelAndBody($car['model_slug'],$car['cabine']);
		
        $car['cabine'] = $this->configuratorModelRepository->getSchemaDescription($this->configuratorModelRepository::$JATO_STANDARD_MAPPER['cabine'], $car['cabine']);
        $car['drive'] = $this->configuratorModelRepository->getSchemaDescription($this->configuratorModelRepository::$JATO_STANDARD_MAPPER['drive'], $car['drive']);

		$discount = $this->discountRepository->findDiscountRange(array('mark' => $carBestseller->getMark(), 'model' => $carBestseller->getModel()),'MAX',array('amount_type' => 'P','main' => true));
		$carneooPrice = $carBestseller->getMinPrice() - $carBestseller->getMinPrice()*($discount['value']-$discount['provision'])/100;

        return [
            'id' => $carBestseller->getId(),
            'alt' => $carBestseller->getMark() . ' ' . $carBestseller->getModel(),
            'src' => '/uploads/cars/'.$image.'.png',
            'name' => $carBestseller->getMark().' '.$carBestseller->getModel(),
            'mark' => $carBestseller->getMark(),
            'model' => $carBestseller->getModel(),
            'price' => $carneooPrice,
            'discount' => ($discount['value']-$discount['provision']),
            'body' => $car['cabine'],
            'drive' => $car['drive'],
            'power' => $car['power'],
            'gear' => $this->configuratorModelRepository->getSchemaDescription($this->configuratorModelRepository::$JATO_STANDARD_MAPPER['gear'], $car['gear']),
            'fuel' => $this->configuratorModelRepository->getSchemaDescription($this->configuratorModelRepository::$JATO_STANDARD_MAPPER['fuel'], $car['fuel']),
            'href' => '/configurator/car-detail/'.$carBestseller->getMark().'/'.$carBestseller->getModel().'/'.$car['cabine'],
        ];
        
    }
}