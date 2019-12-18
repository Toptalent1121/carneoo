<?php

namespace App\Controller;

use App\Entity\Discount;
use App\Entity\TemporaryList;
use App\Repository\ConfiguratorModelRepository;
use App\Repository\TemporaryListRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CheapestModelsController
 *
 * @package App\Controller
 */
class CheapestModelsController extends AbstractController
{
    /**
     * @var EntityManagerInterface $em
     */
    private $em;

    /**
     * DiscountController constructor.
     *
     * @param EntityManagerInterface $em
     */
	public function __construct(EntityManagerInterface $em)
    {
		$this->em = $em;
    }

    public function index()
    {
        return $this->render('cheapest_models/index.html.twig');
    }

    /**
     * @param Request $request
     * @param ConfiguratorModelRepository $configuratorModelRepository
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function list(Request $request, ConfiguratorModelRepository $configuratorModelRepository)
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
        //if(!isset($data['page'])) {
            $page = 1;
        //} else {
        //    $page = $data['page'];
        //}


        if($data['sort'] != '') {
            $sort = $data['sort'];
            $dir = $data['dir'];
        } else {
            $sort = 'mark';
            $dir = 'asc';
        }
		/*
		if($data['per_page'] == 'alle'){
			$limit = 50;
		}else{
			$limit = $data['per_page'];
		}
		*/
		$limit = 10;
		
        $cheapestModelsList = $this->em->getRepository(TemporaryList::class)->getAllDiscounts('price', $page, $sort, $dir, $limit, $filter);
        $list = $this->prepareDiscountsData($cheapestModelsList['result'], $configuratorModelRepository);
		$maxPages = ceil($cheapestModelsList['count'] / $limit);
		/*
        $markArray = array();
        $modelArray = array();
        $model = array();
        $model[]['value'] = '';
        $mark = array();
        $mark[]['value'] = '';

        foreach($cheapestModelsList['result'] as $item) {
            /**
             * @var Discount $item
             */
           /* if(!in_array($item['mark'], $markArray) && $item['mark'] != ''){
                $mark[]['value'] = $markArray[] = $item['mark'];
            }
            if(!in_array($item['model'], $modelArray) && $item['model'] != ''){
                $model[]['value'] = $modelArray[] = $item['model'];
            }
        }

        $filterList = array(
            array(
                'name' => 'Marke',
                'type' => 'select',
                'type_name' => 'mark',
                'option_list' => $mark
            ),
            array(
                'name' => 'Modell',
                'type' => 'select',
                'type_name' => 'model',
                'option_list' => $model
            ),
        );
		*/
        $result = $this->render('cheapest_models/list.html.twig',
            [
                'list' => $list,
            //    'filterList' => $filterList,
                'maxPages' => $maxPages,
                'page' => $page,
            //    'filter' => $filter
            ])->getContent();

        return new JsonResponse($result);
    }

    /**
     * @param $id
     * @param ConfiguratorModelRepository $configuratorModelRepository
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function details($id, ConfiguratorModelRepository $configuratorModelRepository)
    {
        $cheapestModel = $this->em->getRepository(TemporaryList::class)->find($id);

        if($cheapestModel->getVersion()) {
            $criteria = ['model_slug' => $cheapestModel->getModel(),'jato_vehicle_id'=>$cheapestModel->getVersion()];
        } else {
            $criteria = ['model_slug' => $cheapestModel->getModel()];
        }
        $carneooPrice = $configuratorModelRepository->getMinPrice($criteria)->fetchColumn();
        $fields = [
            'mark','jato_vehicle_id', 'model_slug', 'cabine', 'power', 'gear', 'fuel', 'drive', 'doors', 'engine',
        ];
        $standardFields = [
            'consumption', 'energy_class', 'weight',
        ];
        $car = $configuratorModelRepository->getFilteredModels($fields ,$criteria, $standardFields,'cabine')->fetch();
		$image = $configuratorModelRepository->getImageByModelAndBody($car['model_slug'],$car['cabine']);
        $standardOptions = $configuratorModelRepository->getStandardList($car['jato_vehicle_id']);

        $options = [];
        foreach($standardOptions as $standardOption) {
            if(false == in_array($standardOption['schema_id'], $configuratorModelRepository::$STANDARD_OPTIONS_EXCLUSION_SCHEMA_ID)) {
                $options[] = $standardOption['description'];
            }
        }

        $car['capacity'] = $configuratorModelRepository->getOptionValue($car['jato_vehicle_id'],0,'capacity');
        $car['weight_1'] = $configuratorModelRepository->getOptionValue($car['jato_vehicle_id'],0,'weight_1');
        $car['weight_2'] = $configuratorModelRepository->getOptionValue($car['jato_vehicle_id'],0,'weight_2');
        $car['drive'] = $configuratorModelRepository->getSchemaDescription($configuratorModelRepository::$JATO_STANDARD_MAPPER['drive'], $car['drive']);
        $car['gear'] = $configuratorModelRepository->getSchemaDescription($configuratorModelRepository::$JATO_STANDARD_MAPPER['gear'], $car['gear']);
        $car['fuel'] = $configuratorModelRepository->getSchemaDescription($configuratorModelRepository::$JATO_STANDARD_MAPPER['fuel'], $car['fuel']);
        $car['weight'] = $configuratorModelRepository->getOptionValue($car['jato_vehicle_id'],0,'weight_1');
        $car['energy_class'] = substr($car['energy_class'],23);
        $car['consumption'] = [
            $configuratorModelRepository->getOptionValue($car['jato_vehicle_id'],0,'consumption_city'),
            $configuratorModelRepository->getOptionValue($car['jato_vehicle_id'],0,'consumption_country'),
            $configuratorModelRepository->getOptionValue($car['jato_vehicle_id'],0,'consumption_average'),
        ];

        return $this->render('cheapest_models/details.html.twig',
            [
                'discount' => $cheapestModel,
                'car' => $car,
                'options' => $options,
                'image' => '/uploads/cars/'.$image.'.png',
                'price' => $carneooPrice,
            ]);
    }

    /**
     * @param $discounts
     * @param ConfiguratorModelRepository $configuratorModelRepository
     *
     * @return array
     *
     * @throws \Exception
     */
    private function prepareDiscountsData($discounts, ConfiguratorModelRepository $configuratorModelRepository): array
    {
        $data = [];

        if($discounts) {
            foreach ($discounts as $discount) {
                /**
                 * @var Discount $discount
                 */
               
                $car = $configuratorModelRepository->getFilteredModels(array('cabine','model_slug','jato_vehicle_id', 'power', 'gear', 'fuel'),array('model_slug' => $discount['model'],'jato_vehicle_id' => $discount['version']),array(),'cabine')->fetch();
				$image = $configuratorModelRepository->getImageByModelAndBody($car['model_slug'],$car['cabine']);

                $data[] = [
                    'id' => $discount['id'],
                    'alt' => $discount['mark'] . ' ' . $discount['model'],
                    'src' => '/uploads/cars/'.$image.'.png',
                    'name' => $discount['mark'] . ' ' . $discount['model'],
                    'power' => $car['power'],
                    'gear' => $configuratorModelRepository->getSchemaDescription($configuratorModelRepository::$JATO_STANDARD_MAPPER['gear'], $car['gear']),
                    'fuel' => $configuratorModelRepository->getSchemaDescription($configuratorModelRepository::$JATO_STANDARD_MAPPER['fuel'], $car['fuel']),
                    'price' => $discount['sprice'],
                    'discount' => $discount['discount_max'],
                    'href' => $this->generateUrl('max_discount_details', ['id' => $discount['id']]),
                ];
            }
        }

        return $data;
    }
}