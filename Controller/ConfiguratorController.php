<?php

namespace App\Controller;

use App\Entity\Dealer;
use App\Entity\MailQueue;
use App\Form\Front\ConfiguratorLocationType;
use App\Form\Front\ConfiguratorDealerType;
use App\Form\Front\ConfiguratorShareMailType;
use App\Form\Front\UserLoginType;
use App\Form\Front\UserRegisterType;
use App\Form\Front\AccountThanksPasswordType;
use App\Service\Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\ConfiguratorMarkRepository;
use App\Repository\TemporaryListRepository;
use App\Repository\ConfiguratorModelRepository;
use App\Repository\DiscountRepository;
use App\Repository\DealerRepository;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Offer;
use App\Entity\Discount;
use App\Entity\User;
use App\Entity\Stock;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use App\Service\Notifications;

class ConfiguratorController extends HelperController
{
	
	public $session;
	protected $encoder;

    /**
     * @var Notifications $notifications
     */
	private $notifications;

    /**
     * @var Pdf $pdf
     */
	private $pdf;
	
	public function __construct(ConfiguratorModelRepository $confModelEm, DiscountRepository $discountRepository, UserPasswordEncoderInterface $encoder, EventDispatcherInterface $dispatcher, Notifications $notifications, Pdf $pdf)
    {
		$this->encoder = $encoder;
        $this->session = new Session();
		$this->confModelEm = $confModelEm;
		$this->discountRepository = $discountRepository;
		$this->notifications = $notifications;
		$this->dispatcher = $dispatcher;
		$this->notifications = $notifications;
		$this->pdf = $pdf;
    }
	
	public function mark(TemporaryListRepository $temporaryListRepository)
    {
		
		$marks2 = $temporaryListRepository->getActiveMarks();
        $marks = $temporaryListRepository->getActiveMarks(true, true);
		
		return $this->render('config_mark/index.html.twig',
			[
				'marks' => $marks,
				'marks2' => $marks2
		]);
    }

	public function model($mark)
    {
		
		$models = $this->confModelEm->getFilteredModels(array('model_name','model_slug'),array('mark' => $mark),array(),'model_slug');
		$modelList = array();
		$n=0;
		
		foreach($models as $model)
		{
			
			$modelList[$n] = array(
				'name' => $mark == 'AUDI' ? $model['model_slug'] : $model['model_name'],
				'type' => 'card'
			);
			
			$cabines = $this->confModelEm->getFilteredModels(array('doors','cabine','year','jato_vehicle_id','model_slug'),array('model_slug' => $model['model_slug']),array(),'cabine');
			
			foreach($cabines as $cabine)
			{
				
				$minPrice = $this->confModelEm->getMinPrice(array('mark' => $mark,'model_slug' => $model['model_slug'],'cabine' => $cabine['cabine']))->fetchColumn();
			
				$discountMin = $this->getDiscount(array('mark' => $mark,'model' => $model['model_slug'],'body' => $cabine['cabine']), 'MIN', array('amount_type' => 'P', 'main' => true));				
				$discountMax = $this->getDiscount(array('mark' => $mark,'model' => $model['model_slug'],'body' => $cabine['cabine']), 'MAX', array('amount_type' => 'P', 'main' => true));
				$image = $this->confModelEm->getImageByModelAndBody($model['model_slug'],$cabine['cabine']);
				
				if($minPrice > 0 && $discountMax['value'] > 0){
				$modelList[$n]['list'][] = array(
					'href' => '/configurator/car-detail/'.$mark.'/'.$model['model_slug'].'/'.$cabine['cabine'],
					'name' => $mark,
					'model' => ($mark == 'AUDI' ? $model['model_slug'] : $model['model_name']),
					'body' => $this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['cabine'], $cabine['cabine'],'DE',true),
					'data_src' => '/uploads/cars/'.$image.'.png',
					'discount_from' => $discountMin['value'] ? ($discountMin['value']-$discountMin['provision'])*100 : '0.00',
					'discount_to' => $discountMax['value'] ? ($discountMax['value']-$discountMax['provision'])*100 : '0.00',
					'price_from' => $this->calculatePrice($minPrice),
					'efficiency_value' => array(substr($this->confModelEm->getFilteredModels(array('cabine'),array('model_slug' => $model['model_slug'],'cabine' => $cabine['cabine']),array('energy_class'),'cabine')->fetchColumn(2),23)),
					'list' => array(
						array(
							'key'=> 'Leistung',
							'value' => $this->confModelEm->getMinPower(array('model_slug' => $model['model_slug'],'cabine' => $cabine['cabine']))->fetchColumn().' - '.$this->confModelEm->getMaxPower(array('model_slug' => $model['model_slug'],'cabine' => $cabine['cabine']))->fetchColumn().' PS'
						),
						array(
							'key'=> 'Kraftstoffverbrauch',
							'value' => substr($this->confModelEm->getFilteredModels(array('cabine'),array('model_slug' => $model['model_slug'],'cabine' => $cabine['cabine']),array('consumption'),'cabine')->fetchColumn(2),20)
						),
						array(
							'key'=> 'CO-Emission',
							'value' => substr($this->confModelEm->getFilteredModels(array('cabine'),array('model_slug' => $model['model_slug'],'cabine' => $cabine['cabine']),array('co_emission'),'cabine')->fetchColumn(2),18)
						)
					),
					'has_overlay' => true,
					'mod' => 'card-car image-s',
					'image_mod' => 'owl-lazy'
				);
			
				}else{
					unset($modelList[$n]);
				}
			}
			$n++;		
		}
		
		return $this->render('config_model/index.html.twig',
			[
				'mark' => $mark,
				'model_list' => $modelList
		]);
    }

	public function carDetails($mark,$model_slug, $cabine)
    {
      	
		//clear configurator session
		$this->clearSession();
		
		$this->session->set('mark', $mark);
		$this->session->set('model_slug', $model_slug);
		$this->session->set('cabine', $cabine);
		
		$car = $this->confModelEm->getFilteredModels(array('mark','model_name','model_slug','doors','cabine','year','jato_vehicle_id'),array('model_slug' => $model_slug,'cabine'=>$cabine),array('size_out','size_in','cargo'),'cabine')->fetch();
		$image = $this->confModelEm->getImageByModelAndBody($model_slug,$cabine);
		
		$aside = $this->getAside(array('model_slug' => $model_slug, 'cabine' => $cabine));
		
		$carMedia = $this->getCarMedia($car);
		
		$carMedia = array(
			'text_heading' => $car['mark'].' '.$car['model_name'].' '.$this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['cabine'], $car['cabine'],'DE',true),
			'data_src_logo' => '/uploads/mark_logo/'.$car['logo'],
			'alt_logo' => $car['mark'].' logo',
			'data_src_image' => '/uploads/cars/'.$image.'.png',
			'alt_image' => $car['mark'].' '.$car['model_name'],
			'text_anchor_button' => 'KONFIGURIEREN',
			'href' => '/stock/list/'.$mark.'/'.$model_slug.'/'.$cabine
		);
		
		$minPrice = $this->confModelEm->getMinPrice(array('mark' => $mark,'model_slug' => $model_slug,'cabine' => $cabine))->fetchColumn();
		
		$discountMin = $this->getDiscount(array('mark' => $mark,'model' => $model_slug,'body' => $cabine), 'MIN', array('amount_type' => 'P', 'main' => true));
		$discountMax = $this->getDiscount(array('mark' => $mark,'model' => $model_slug,'body' => $cabine), 'MAX', array('amount_type' => 'P', 'main' => true));
				
		$discountPrice = array(
			'discount_text' => 'Rabatt',
			'discount_from' => $discountMin['value'] ? ($discountMin['value']-$discountMin['provision'])*100 : '0.00',
			'discount_to' => $discountMax['value'] ? ($discountMax['value']-$discountMax['provision'])*100 : '0.00',
			'price' => $this->calculatePrice($minPrice),
			'saved' => $discountMax['value'] ? ($discountMax['value']-$discountMax['provision'])*$minPrice/100 : '0.00',
		);
		
		$carSpec[] = array(
			'name' => 'Gewicht',
			'type' => 'kv',
			'list' => array(
				array(
					'key' => ucfirst($this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['weight_1'],'')),
					'value' => $this->confModelEm->getOptionValue($car['jato_vehicle_id'],0,'weight_1')
				),
				array(
					'key' => ucfirst($this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['weight_2'],'')),
					'value' => $this->confModelEm->getOptionValue($car['jato_vehicle_id'],0,'weight_2')
				),
				array(
					'key' => ucfirst($this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['weight_3'],'')),
					'value' => $this->confModelEm->getOptionValue($car['jato_vehicle_id'],0,'weight_3')
				)
			)
		);		
		
		if(isset($car['engine_desc'])){
			$motor = array();
			$i=0;
			$motorArrays = explode(', ',$car['engine_desc']);
			foreach($motorArrays as $array)
			{
				if($i==0){
					$motor[$i]['key'] = 'Typ';
					$motor[$i]['value'] = $array;
				} elseif(strpos($array, ':')) {
					$row = explode(':',$array);
					$motor[$i]['key'] = trim($row[0]);
					$motor[$i]['value'] = trim($row[1]);
				}
				$i++;			
			}
			$carSpec[] = array(
				'name' => 'Motor',
				'type' => 'kv',
				'list' => $motor
			);
		}
		
		$carSpec[] = array(
			'name' => 'Verbrauch',
			'type' => 'kv',
			'list' => array(
				array(
					'key' => $this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['consumption_city'],''),
					'value' => $this->confModelEm->getOptionValue($car['jato_vehicle_id'],0,'consumption_city')
				),
				array(
					'key' => $this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['consumption_country'],''),
					'value' => $this->confModelEm->getOptionValue($car['jato_vehicle_id'],0,'consumption_country')
				),
				array(
					'key' => $this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['consumption_average'],''),
					'value' => $this->confModelEm->getOptionValue($car['jato_vehicle_id'],0,'consumption_average')
				)
			)
		);
		
		$size = array();
		$i=0;
		$sizeArrays = explode(', ',substr($car['size_out'],20));
		foreach($sizeArrays as $array)
		{
			if(strpos($array, ':'))
			{
				$row = explode(':',$array);
				if($i==0){
					$size[$i]['key'] = 'Länge (mm)';
					$size[$i]['value'] = substr(trim($row[0]),0,5);
				}else{
					$size[$i]['key'] = trim($row[0]);
					if($i == count($sizeArrays)-1)
						$size[$i]['value'] = substr(trim($row[1]),0,5);
					else
						$size[$i]['value'] = trim($row[1]);
				}
				$i++;
			}
		}
		$carSpec[] = array(
			'name' => 'Abmessungen',
			'type' => 'kv',
			'list' => $size
		);
		
		$cargo = array();
		$i=0;
		if(strpos(substr($car['cargo'],19), 'und')){
			$cargoArrays = explode('und',substr($car['cargo'],19));
		} else {
			$cargoArrays[] = substr($car['cargo'],19);
		}
		
		if(count($cargoArrays) > 1){
		
			foreach($cargoArrays as $array)
			{
				$row = explode(':',$array);
				$cargo[$i]['key'] = trim($row[0]);
				$cargo[$i]['value'] = trim($row[1]);
				$i++;
			}
		
			$carSpec[] = array(
				'name' => 'Gepäckraumvolumen',
				'type' => 'kv',
				'list' => $cargo
			);
		}
		
		return $this->render('config_car_details/index.html.twig',
		[
			'car_media_o' => $carMedia,
			'temp_navigation' => $aside,
			'car_spec' => $carSpec,
			'discount_price_opts' => $discountPrice
		]);
    }
	
	public function group()
	{
		$data = $this->getSessionData();
		
		if($data['model_slug'] == null){
			return $this->redirectToRoute('configurator_mark');
		}
		
		$private = ($this->getDiscount(array('mark' => $data['mark'],'model' => $data['model_slug'],'body' => $data['cabine']),'MAX', array('amount_type' => 'P', 'main' => true),'P') != false ? true : false);
		$firm = ($this->getDiscount(array('mark' => $data['mark'],'model' => $data['model_slug'],'body' => $data['cabine']),'MAX', array('amount_type' => 'P', 'main' => true),'F') != false ? true : false);
		$disabled = ($this->getDiscount(array('mark' => $data['mark'],'model' => $data['model_slug'],'body' => $data['cabine']),'MAX', array('amount_type' => 'P', 'main' => true),'D') != false ? true : false);
		
		
		return $this->render('config_group/index.html.twig',
		[
			'private' => $private,
			'firm' => $firm,
			'disabled' => $disabled		
		]);
	}
	
	public function variants($group)
    {

		$this->session->set('group', $group);	
		
		$data = $this->getSessionData();
		
		if($data['model_slug'] == null){
			return $this->redirectToRoute('configurator_mark');
		}
		
		$aside = $this->getAside($data);
		
		$carMedia = $this->getCarMedia($data);
		$minPrice = $this->confModelEm->getMinPrice(array('mark' => $data['mark'],'model_slug' => $data['model_slug'],'cabine' => $data['cabine']))->fetchColumn();
		
		$discountMax = $this->getDiscount(array('mark' => $data['mark'],'model' => $data['model_slug'],'body' => $data['cabine']),'MAX', array('amount_type' => 'P', 'main' => true),$data['group']);
		
		$discountPrice = $this->getDiscountPrice($minPrice,$discountMax);
		
		$variants = $this->confModelEm->getFilteredModels(array('mark','model_name','cabine','fuel','drive','variant','variant_name','jato_vehicle_id','M_price'),array('mark' => $data['mark'],'model_slug' => $data['model_slug'],'cabine' => $data['cabine']),array(),'variant');
		$n=1;
		$variantList = array();
		$disabled = true;
		
		foreach($variants as $variant)
		{	
	
			$standardOptions = $this->confModelEm->getStandardList($variant['jato_vehicle_id']);
			
			$description = '<ul>';
			foreach($standardOptions as $standardOption)
			{
				if(!strstr($standardOption['description'],'Kraftstoff') && !strstr($standardOption['description'],'(ccm)'))
					$description .= '<li>'.$standardOption['description'].'</li>';
			}
			$description .= '</ul>';
			
			
			$variantList[$n] = array(
				'name' => $variant['variant_name'] == '-' ? ($variant['variant'] != '-' ? 'Standard '.$variant['variant'] : 'Standard') : $variant['variant_name'],
				'card_id' => $n,
				'input' => array( 
					'name' => 'variant',
					'id' => $n,
					'value' => $variant['variant'],
					'disabled' => false,
					'type' => 'radio',
					//'displaytype' => 'link'
				),
				'price_from' => $this->calculatePrice($variant['price']),
				'description' => $description,
				'has_overlay' => true,
				'mod' => 'card-car-feature card-form'
			);
			
			if($data['variant'] == $variant['variant']){
				
				$variantList[$n]['input']['checked'] = true;
				$disabled = false;
			}
				
			/*
			
			$optionList = $this->confModelEm->getOptionByType($variant['jato_vehicle_id'],'O');
			
			foreach($optionList as $option)
			{
			
				$variantList[$n]['deps']['optional'][] = array(
					'name' => $option['name'],
					'card_id' => 'optional'.$n,
					'is_optional' => true,
					'input' => array(
						'name' => 'option[]',
						'id' => 'option_'.$option['option_id'],
						'type' => 'checkbox',
						'value' => $option['option_id']
					),
					'price_add' => $option['price'],
					'description' => $this->confModelEm->getOptionDescription($variant['jato_vehicle_id'],$option['option_id']),
					'mod' => 'card-car-feature'
				);
			
			}
			*/
			$n++;			
		}
			
		return $this->render('config_variants/index.html.twig',
		[
			'car_media_o' => $carMedia,
			'temp_navigation' => $aside,
			'variant_list' => $variantList,
			'discount_price_opts' => $discountPrice,
			'disabled' => $disabled
		
		]);
    }

    public function engine(Request $request)
    {
		if($request->get('variant') != null){
			
			$this->session->set('variant', $request->get('variant'));
		}
		
		$data = $this->getSessionData();
		if($data['variant'] == null){
			return $this->redirectToRoute('configurator_mark');
		}
		
		$aside = $this->getAside($data);
		
		$carMedia = $this->getCarMedia($data);
				
		$minPrice = $this->confModelEm->getMinPrice(array('mark' => $data['mark'],'model_slug' => $data['model_slug'],'cabine' => $data['cabine'],'variant' => $data['variant']))->fetchColumn();
		
		$discountMax = $this->getDiscount(array('mark' => $data['mark'],'model' => $data['model_slug'],'body' => $data['cabine']),'MAX', array('amount_type' => 'P', 'main' => true),$data['group']);
				
		$discountMin = $this->getDiscount(array('mark' => $data['mark'],'model' => $data['model_slug'],'body' => $data['cabine']),'MAX', array('amount_type' => 'P', 'main' => true),$data['group']);
				
		$discountPrice = $this->getDiscountPrice($minPrice,$discountMax);
		
		$models = $this->confModelEm->getFilteredModels(array('engine','fuel'),array('model_slug' => $data['model_slug'],'cabine' => $data['cabine'],'variant' => $data['variant']));
		
		$category = array();
		foreach($models as $model)
		{
			$category[$model['fuel']][] = $model['engine'];
			$category[$model['fuel']] = array_unique($category[$model['fuel']]);
		}

		$engineList = array();
		$disabled = true;
		$n=0;
		$x=1;
		foreach($category as $fuel => $engineData)
		{			
			sort($engineData);
			foreach($engineData as $engine)
			{
			
			$engineList[$n] = array(
				'name' => ($fuel == 'E' ? '' : number_format($engine,1).' ').ucfirst($this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['fuel'], $fuel)),
				'type' => 'card'
			);
			
			$items = $this->confModelEm->getFilteredModels(array('power','gear','price','version','jato_vehicle_id','drive','fuel','doors'),array('model_slug' => $data['model_slug'],'cabine' => $data['cabine'],'variant' => $data['variant'],'engine' => $engine,'fuel' => $fuel),array());
			foreach($items as $item)
			{
				$modelMinPrice = $this->confModelEm->getMinPrice(array('jato_vehicle_id' => $item['jato_vehicle_id']))->fetchColumn();
				$engineList[$n]['list'][$x] = array(
					'name' => $item['version'],
					'card_id' => $x,
					'input' => array(
						'name' => 'version',
						'id' => $x,
						'type' => 'radio',
						//'displaytype' => 'link',
						'value' => $item['jato_vehicle_id']
					),
					'discount_from' => $discountMin['value'] ? ($discountMin['value']-$discountMin['provision'])*100 : '0.00',
					'discount_to' => $discountMax['value'] ? ($discountMax['value']-$discountMax['provision'])*100 : '0.00',
					'price_from' => $this->calculatePrice($modelMinPrice),
					'efficiency_value' => array(substr($this->confModelEm->getFilteredModels(array('jato_vehicle_id'),array('jato_vehicle_id' => $item['jato_vehicle_id']),array('energy_class'))->fetchColumn(2),23)),
					'list' => array(
						array(
							'key'=> 'Leistung',
							'value' => $item['power'].' PS'
						),
						array(
							'key'=> 'Kraftstoffverbrauch',
							'value' => substr($this->confModelEm->getFilteredModels(array('jato_vehicle_id'),array('jato_vehicle_id' => $item['jato_vehicle_id']),array('consumption'))->fetchColumn(2),20)
						),
						array(
							'key'=> 'CO-Emission',
							'value' => substr($this->confModelEm->getFilteredModels(array('jato_vehicle_id'),array('jato_vehicle_id' => $item['jato_vehicle_id']),array('co_emission'))->fetchColumn(2),18)
						)
					),
					'description' => '<p><strong>'.$this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['drive'],null).': </strong>'.$this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['drive'], $item['drive']).'</p><p><strong>'.$this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['gear'],null).': </strong>'.ucfirst($this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['gear'], $item['gear'])).'</p><p><strong>'.$this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['power'],null).': </strong>'.$item['power'].'</p><p><strong>'.$this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['doors'],null).': </strong>'.$item['doors'].'</p>',
					'has_overlay' => true,
					'mod' => 'card-car-feature card-form'
				);
				
				if($data['version'] == $item['jato_vehicle_id']){
				
					$engineList[$n]['list'][$x]['input']['checked'] = true;
					$disabled = false;
				}
				$x++;
			}
			$n++;
			}
		}
		
		$itemList = $this->getItemList($discountMax,$data); 
				
		return $this->render('config_engine/index.html.twig',
		[
			'car_media_o' => $carMedia,
			'temp_navigation' => $aside,
			'group' => $data['group'],
			'engine_list' => $engineList,
			'discount_price_opts' => $discountPrice,
			'item_list' => $itemList,
			'disabled' => $disabled
		
		]);
    }

    public function color(Request $request)
    {
        
		if($request->get('version') != null){
			
			$this->session->set('version', $request->get('version'));
		}
		
		$data = $this->getSessionData();
		if($data['version'] == null){
			return $this->redirectToRoute('configurator_mark');
		}
		
		$aside = $this->getAside($data);
		
		$carMedia = $this->getCarMedia($data);
		$minPrice = $this->confModelEm->getMinPrice(array('jato_vehicle_id' => $data['version']))->fetchColumn();
		
		$discountMax = $this->getDiscount(array('mark' => $data['mark'],'model' => $data['model_slug'],'body' => $data['cabine'],'version' => $data['version']),'MAX', array('amount_type' => 'P', 'main' => true),$data['group']);
		
		//price header
		$discountPrice = $this->getDiscountPrice($minPrice,$discountMax);
		
		$colorArray = $this->getColors($data['version']);
				
		$result = $this->confModelEm->getEquipment($data['version'],'C',$this->confModelEm::$JATO_STANDARD_MAPPER['color']);
		
		$n=0;
		$disabled = true;
		$colorList = array();
		$colors = array();
		foreach($result as $color)
		{
			if($color['value'] != 'CUS'){
					
				$colors[$n] = $colorArray[$color['code']];
	
				$extendedColors = $this->confModelEm->getOptionBuild($data['version'],$color['option_id'],100006)->fetch();
				$rule = $this->parseRule($extendedColors['option_rule']);
				
				if(count($rule) > 0 && $rule[0] > 0)
					$object = $this->confModelEm->getOption($data['version'],$rule[0]);
				else
					$object = $color;
				
				$colorList[$n] = array(
					'name' => $color['name'],
					'card_id' => $color['option_id'],
					'data_color' => $colorArray[$color['code']],
					'info_color' => $colorArray[$color['code']],
					'price_add' => $object['price'] == 0 ? '0.00' : $this->calculatePrice($object['price']),
					'description' => $object['name'],
					'has_overlay' => true,
					'mod' => 'card-car-feature card-form color-picker price-add'					
				);
				
				$options = array_merge($data['packet'],array($data['color']));
					
				$includes = $this->checkIncludes($options,$color['option_id'],'radio','color',false,false);	
				$colorList[$n] = array_merge($colorList[$n],$includes['response']);
				
				if(isset($includes['disabled']))
					$disabled = $includes['disabled'];
				
				$n++;
			}
		}
		
		//packet list
		$itemList = $this->getItemList($discountMax,$data); 
		
		return $this->render('config_color/index.html.twig',
		[
			'car_media_o' => $carMedia,
			'temp_navigation' => $aside,
			'color_list' => $colorList,
			'colors' => array_unique($colors),
			'discount_price_opts' => $discountPrice,
			'item_list' => $itemList,
			'disabled' => $disabled
		]);
    }
	
	public function package(Request $request)
    {
		
		if($request->get('color') != null){
			
			$this->session->set('color', $request->get('color'));
		}
		
		if($request->get('packet') != null){
			
			$packetString = serialize($request->get('packet'));
			$this->session->set('packet', $packetString);
		}
		
		if($request->get('add') && $request->get('remove')){
			
			$this->calculateOptions($request->get('add'), $request->get('remove'),'packet');
		}
		
		$data = $this->getSessionData();
		
		if($data['model_slug'] == null){
			return $this->redirectToRoute('configurator_mark');
		}		
		
		$aside = $this->getAside($data);
		$carMedia = $this->getCarMedia($data);
		
		$minPrice = $this->confModelEm->getMinPrice(array('jato_vehicle_id' => $data['version']))->fetchColumn();
		$discountMax = $this->getDiscount(array('mark' => $data['mark'],'model' => $data['model_slug'],'body' => $data['cabine'],'version' => $data['version']),'MAX', array('amount_type' => 'P', 'main' => true),$data['group']);
		
		//price header
		$discountPrice = $this->getDiscountPrice($this->getItemList(null,$data,$mode='sum'),$discountMax);

		$itemList = $this->getItemList($discountMax,$data);
		
		$packetList2 = array();
		
		if($data['mark'] == 'OPEL'){
			
			$packet2 = $this->confModelEm->getEquipment($data['version'],'P','!'.$this->confModelEm::$JATO_STANDARD_MAPPER['months'],null,'option_id')->fetchAll();
			$packetList2 = $this->getCategoryList($packet2,'packet');
		
			$months = $this->confModelEm->getEquipment($data['version'],'P',$this->confModelEm::$JATO_STANDARD_MAPPER['months'],null,'value');	
			//if($months){
				$n = 1;
				$x = 1;			
				foreach($months as $month)
				{
					$packetList[$n] = array(
						'name' => 'Opel FLEXCARE Paket '.$month['value'].' Monaten',
						'type' => 'card',
					);
					
					$packets = $this->confModelEm->getEquipment($data['version'],'P',$this->confModelEm::$JATO_STANDARD_MAPPER['months'],$month['value'],'option_id');
					foreach($packets as $packet)
					{
						$descriptionList = $this->confModelEm->getOptionDescription($data['version'],$packet['option_id']);
						$description = '<ul>';
						foreach($descriptionList as $list)
						{
							$description .= '<li>'.$list['description'].'</li>';
						}
						$description .= '</ul>';
						
						$packetList[$n]['list'][$x] = array(
							'name' => $packet['name'],
							'card_id' => $packet['option_id'],
							'price_add' => $this->calculatePrice($packet['price']),
							//'data_src' => '', //image
							'description' => $description,
							'has_overlay' => true,
							'image_mod' => 'owl-lazy'
						);
						
						$includes = $this->checkIncludes($data['packet'],$packet['option_id'],'checkbox','packet[]');						
						$packetList[$n]['list'][$x] = array_merge($packetList[$n]['list'][$x],$includes['response']);						
						$x++;
					}
					$n++;			
				}
			//}
			$type = 'category';

		}else{
		
			$packet = $this->confModelEm->getEquipment($data['version'],'P',null,null,'option_id')->fetchAll();
			
			//redirect to next step if no data
			if(!$packet){
				
				if($request->get('action') == 'back'){
					return $this->redirectToRoute('configurator_color');
				}else{
					return $this->redirectToRoute('configurator_rims');
				}			
			}
			
			$packetList = $this->getCategoryList($packet,'packet');
			$type = 'cards';
		}
		
		return $this->render('config_package/index.html.twig',
		[
			'car_media_o' => $carMedia,
			'temp_navigation' => $aside,
			'packet_list' => $packetList,
			'packet_list2' => $packetList2,
			'discount_price_opts' => $discountPrice,
			'item_list' => $itemList,
			'type' => $type
		]);
    }
	
	public function rims(Request $request)
    {
        if($request->get('packet') != null){
			
			$packetString = serialize($request->get('packet'));
			$this->session->set('packet', $packetString);
		}
		
		$data = $this->getSessionData();
		
		if($data['model_slug'] == null){
			return $this->redirectToRoute('configurator_mark');
		}
		
		$aside = $this->getAside($data);
		$carMedia = $this->getCarMedia($data);
		
		$minPrice = $this->confModelEm->getMinPrice(array('jato_vehicle_id' => $data['version']))->fetchColumn();
		
		$discountMax = $this->getDiscount(array('mark' => $data['mark'],'model' => $data['model_slug'],'body' => $data['cabine'],'version' => $data['version']),'MAX', array('amount_type' => 'P', 'main' => true),$data['group']);
		
		//price header
		$discountPrice = $this->getDiscountPrice($this->getItemList(null,$data,$mode='sum'),$discountMax);
		
		//packet list		
		$itemList = $this->getItemList($discountMax,$data);
		
		$rimList = array();
		$standard = $this->confModelEm->getStandardDescription('rims',$data['version']);
		if($standard){
			$rimList[0] = array(
				'name' => 'Standardfelgen',
				'type' => 'card'
			);
			$rimList[0]['list'][] = array(
				'name' => 'Standardfelgen',
				'card_id' => 1,
				'input' => array(
					'name' => 'rim',
					'id' => 1,
					'type' => 'radio',
					'displaytype' => 'checkbox',
					'value' => 1,
					'checked' => true
				),
				'price_add' => '0.00',
				//'data_src' => '', //image
				'description' => $standard,
				'has_overlay' => true,
				'image_mod' => 'owl-lazy',
				'mod' => 'card-car-feature card-form price-add'
			);
		}
		
		$rimSizes = $this->confModelEm->getEquipment($data['version'],'O',$this->confModelEm::$JATO_STANDARD_MAPPER['rims_desc'],null,'value');	
		$n = 1;
		$x = 1;
		$disabled = true;
		foreach($rimSizes as $rimSize)
		{
			$rimList[$n] = array(
				'name' => trim($this->confModelEm->getSchemaDescription($rimSize['schema_id'],$rimSize['value'])).'"',
				'type' => 'card',
			);
			
			$rims = $this->confModelEm->getEquipment($data['version'],'O',$this->confModelEm::$JATO_STANDARD_MAPPER['rims_desc'],$rimSize['value'],'option_id');
			foreach($rims as $rim)
			{
				$descriptionList = $this->confModelEm->getOptionDescription($data['version'],$rim['option_id']);
				$description = '<ul>';
				foreach($descriptionList as $list)
				{
					$description .= '<li>'.$list['description'].'</li>';
				}
				$description .= '</ul>';
				
				$rimList[$n]['list'][$x] = array(
					'name' => $rim['name'],
					'card_id' => $rim['option_id'],
					'price_add' => $rim['price'] ? $this->calculatePrice($rim['price']) : '0.00',
					//'data_src' => '', //image
					'description' => $description,
					'has_overlay' => true,
					'image_mod' => 'owl-lazy'
				);
				
				$options = array_merge($data['packet'],array($data['rim']));
				
				$includes = $this->checkIncludes($options,$rim['option_id'],'radio','rim',false,false);
				
				$rimList[$n]['list'][$x] = array_merge($rimList[$n]['list'][$x],$includes['response']);
				
				if(isset($includes['disabled']))
					$disabled = $includes['disabled'];
				
				$x++;
			}
			$n++;			
		}
		
		return $this->render('config_rims/index.html.twig',
		[
			'car_media_o' => $carMedia,
			'temp_navigation' => $aside,
			'rim_list' => $rimList,
			'discount_price_opts' => $discountPrice,
			'item_list' => $itemList,
			'disabled' => $disabled
		]);
    }
	
	public function interior(Request $request)
    {
        if($request->get('rim') != null){
			
			$this->session->set('rim', $request->get('rim'));
		}
		
		$data = $this->getSessionData();		
		if($data['model_slug'] == null){
			return $this->redirectToRoute('configurator_mark');
		}
		
		$aside = $this->getAside($data);
		$carMedia = $this->getCarMedia($data);
		
		$minPrice = $this->confModelEm->getMinPrice(array('jato_vehicle_id' => $data['version']))->fetchColumn();
		
		$discountMax = $this->getDiscount(array('mark' => $data['mark'],'model' => $data['model_slug'],'body' => $data['cabine'],'version' => $data['version']),'MAX', array('amount_type' => 'P', 'main' => true),$data['group']);
		
		//price header
		$discountPrice = $this->getDiscountPrice($this->getItemList(null,$data,$mode='sum'),$discountMax);
		
		//packet list		
		$itemList = $this->getItemList($discountMax,$data);
		
		$polsters = $this->confModelEm->getEquipment($data['version'],'C',$this->confModelEm::$JATO_STANDARD_MAPPER['polster']);
		$n=0;
		$disabled = true;
		foreach($polsters as $polster)
		{
			$descriptionList = $this->confModelEm->getOptionDescription($data['version'],$polster['option_id']);
			$description = '<ul>';
			foreach($descriptionList as $list)
			{
				$description .= '<li>'.$list['description'].'</li>';
			}
			$description .= '</ul>';
			
			$extendedPolster = $this->confModelEm->getOptionBuild($data['version'],$polster['option_id'],100006)->fetch();
			$rule = $this->parseRule($extendedPolster['option_rule']);
			
			if(count($rule) > 0 && $rule[0] > 0)				
				$object = $this->confModelEm->getOption($data['version'],$rule[0]);
			else
				$object = $polster;
			
			$polsterList[$n] = array(
				'name' => $polster['name'],
				'card_id' => $polster['option_id'],			
				'info_color' => '#FFF',
				'price_add' => $object['price'] == 0 ? '0.00' : $this->calculatePrice($object['price']),
				'description' => $description,
				'has_overlay' => true				
			);
			
			$options = array_merge($data['packet'],array($data['polster']));
			
			$includes = $this->checkIncludes($options,$polster['option_id'],'radio','polster',false,false);
			
			$polsterList[$n] = array_merge($polsterList[$n],$includes['response']);
			
			if(isset($includes['disabled']))
				$disabled = $includes['disabled'];
				
			$n++;
		}
		
        return $this->render('config_interior/index.html.twig',
		[
			'car_media_o' => $carMedia,
			'temp_navigation' => $aside,
			'polster_list' => $polsterList,
			'discount_price_opts' => $discountPrice,
			'item_list' => $itemList,
			'disabled' => $disabled
		]);
    }
	
	public function exterior(Request $request)
    {
        if($request->get('polster') != null){
			
			$this->session->set('polster', $request->get('polster'));
		}
		
		if($request->get('exterior') != null){
			
			$exteriorString = serialize($request->get('exterior'));			
			$this->session->set('exterior', $exteriorString);
		}
		
		if($request->get('add') && $request->get('remove')){
			
			$this->calculateOptions($request->get('add'), $request->get('remove'),'exterior');
		}
		
		$data = $this->getSessionData();		
		if($data['model_slug'] == null){
			return $this->redirectToRoute('configurator_mark');
		}
		
		$aside = $this->getAside($data);
		$carMedia = $this->getCarMedia($data);
		
		$minPrice = $this->confModelEm->getMinPrice(array('jato_vehicle_id' => $data['version']))->fetchColumn();
		
		$discountMax = $this->getDiscount(array('mark' => $data['mark'],'model' => $data['model_slug'],'body' => $data['cabine'],'version' => $data['version']),'MAX', array('amount_type' => 'P', 'main' => true),$data['group']);
		
		//price header
		$discountPrice = $this->getDiscountPrice($this->getItemList(null,$data,$mode='sum'),$discountMax);
		
		//packet list		
		$itemList = $this->getItemList($discountMax,$data);
		
		//categories
		$light = $this->confModelEm->getEquipment($data['version'],'O',null,null,'option_id','Lights')->fetchAll();
		$lightList = $this->getCategoryList($light,'exterior');
				
		$body = $this->confModelEm->getEquipment($data['version'],'O',null,null,'option_id','Exterior')->fetchAll();
		$bodyList = $this->getCategoryList($body,'exterior');
		
		$storage = $this->confModelEm->getEquipment($data['version'],'O',null,null,'option_id','Storage')->fetchAll();
		$storageList = $this->getCategoryList($storage,'exterior');
		
		$roof = $this->confModelEm->getEquipment($data['version'],'O',null,null,'option_id','Roof')->fetchAll();
		$roofList = $this->getCategoryList($roof,'exterior');
		
		//redirect to next step if no data
		if(!$light && !$body && !$storage && !$roof){
			
			if($request->get('action') == 'back'){
				return $this->redirectToRoute('configurator_interior');
			}else{
				return $this->redirectToRoute('configurator_multimedia');
			}
		}
		
        return $this->render('config_exterior/index.html.twig',
		[
			'car_media_o' => $carMedia,
			'temp_navigation' => $aside,
			'light_list' => $lightList,
			'body_list' => $bodyList,
			'storage_list' => $storageList,
			'roof_list' => $roofList,
			'discount_price_opts' => $discountPrice,
			'item_list' => $itemList
		]);
    }
	
	public function multimedia(Request $request)
    {
		if($request->get('exterior') != null){
			
			$exteriorString = serialize($request->get('exterior'));			
			$this->session->set('exterior', $exteriorString);
		}
		
		if($request->get('audio') != null){
			
			$audioString = serialize($request->get('audio'));			
			$this->session->set('audio', $audioString);
		}
		
		if($request->get('add') && $request->get('remove')){
			
			$this->calculateOptions($request->get('add'), $request->get('remove'),'audio');
		}
		
		$data = $this->getSessionData();	
		if($data['model_slug'] == null){
			return $this->redirectToRoute('configurator_mark');
		}
		
		$aside = $this->getAside($data);
		$carMedia = $this->getCarMedia($data);
		
		$minPrice = $this->confModelEm->getMinPrice(array('jato_vehicle_id' => $data['version']))->fetchColumn();
		
		$discountMax = $this->getDiscount(array('mark' => $data['mark'],'model' => $data['model_slug'],'body' => $data['cabine'],'version' => $data['version']),'MAX', array('amount_type' => 'P', 'main' => true),$data['group']);
		
		//price header
		$discountPrice = $this->getDiscountPrice($this->getItemList(null,$data,$mode='sum'),$discountMax);
		
		//packet list		
		$itemList = $this->getItemList($discountMax,$data);
			
		$audio = $this->confModelEm->getEquipment($data['version'],'O',null,null,'option_id','Audio')->fetchAll();
		
		//redirect to next step if no data
		if(!$audio){
			
			if($request->get('action') == 'back'){
				return $this->redirectToRoute('configurator_exterior');
			}else{
				return $this->redirectToRoute('configurator_safety');
			}
		}

		$audioList = $this->getCategoryList($audio,'audio');
		
        return $this->render('config_multimedia/index.html.twig',
		[
			'car_media_o' => $carMedia,
			'temp_navigation' => $aside,
			'audio_list' => $audioList,
			'discount_price_opts' => $discountPrice,
			'item_list' => $itemList
		]);
    }

    public function safety(Request $request)
    {
		if($request->get('audio') != null){
			
			$audioString = serialize($request->get('audio'));			
			$this->session->set('audio', $audioString);
		}
		
		if($request->get('safety') != null){
			
			$safetyString = serialize($request->get('safety'));			
			$this->session->set('safety', $safetyString);
		}
		
		if($request->get('add') && $request->get('remove')){
			
			$this->calculateOptions($request->get('add'), $request->get('remove'),'safety');
		}
		
		$data = $this->getSessionData();		
		if($data['model_slug'] == null){
			return $this->redirectToRoute('configurator_mark');
		}
		
		$aside = $this->getAside($data);
		$carMedia = $this->getCarMedia($data);
		
		$minPrice = $this->confModelEm->getMinPrice(array('jato_vehicle_id' => $data['version']))->fetchColumn();
		
		$discountMax = $this->getDiscount(array('mark' => $data['mark'],'model' => $data['model_slug'],'body' => $data['cabine'],'version' => $data['version']),'MAX', array('amount_type' => 'P', 'main' => true),$data['group']);
		
		//price header
		$discountPrice = $this->getDiscountPrice($this->getItemList(null,$data,$mode='sum'),$discountMax);
		
		//packet list		
		$itemList = $this->getItemList($discountMax,$data);
		
		$save = $this->confModelEm->getEquipment($data['version'],'O',null,null,'option_id','Safety')->fetchAll();
		$locks = $this->confModelEm->getEquipment($data['version'],'O',null,null,'option_id','Locks')->fetchAll();
		$safety = array_merge($save,$locks);
		
		//redirect to next step if no data
		if(count($safety) == 0){
			
			if($request->get('action') == 'back'){
				return $this->redirectToRoute('configurator_multimedia');
			}else{
				return $this->redirectToRoute('configurator_misc');
			}
		}
		
		$safetyList = $this->getCategoryList($safety,'safety');
		
        return $this->render('config_safety/index.html.twig',
		[
			'car_media_o' => $carMedia,
			'temp_navigation' => $aside,
			'safety_list' => $safetyList,
			'discount_price_opts' => $discountPrice,
			'item_list' => $itemList
		]);
    }
	
	public function misc(Request $request)
    {
		if($request->get('safety') != null){
			
			$safetyString = serialize($request->get('safety'));			
			$this->session->set('safety', $safetyString);
		}
		
		if($request->get('misc') != null){
			
			$miscString = serialize($request->get('misc'));			
			$this->session->set('misc', $miscString);
		}
		
		if($request->get('add') && $request->get('remove')){
			
			$this->calculateOptions($request->get('add'), $request->get('remove'),'misc');
		}
		
		$data = $this->getSessionData();		
		if($data['model_slug'] == null){
			return $this->redirectToRoute('configurator_mark');
		}
		
		$aside = $this->getAside($data);
		$carMedia = $this->getCarMedia($data);
		
		$minPrice = $this->confModelEm->getMinPrice(array('jato_vehicle_id' => $data['version']))->fetchColumn();
		
		$discountMax = $this->getDiscount(array('mark' => $data['mark'],'model' => $data['model_slug'],'body' => $data['cabine'],'version' => $data['version']),'MAX', array('amount_type' => 'P', 'main' => true),$data['group']);
		
		//price header
		$discountPrice = $this->getDiscountPrice($this->getItemList(null,$data,$mode='sum'),$discountMax);
		
		//packet list
        $itemList = $this->getItemList($discountMax,$data);
		
		$miscList = array();
		$cargo = $this->confModelEm->getEquipment($data['version'],'O',null,null,'option_id','Cargo')->fetchAll();
		if($cargo){
			$miscList[] = array(
				'name' => 'Stauraumsicherung',
				'type' => 'card',
				'list' => $this->getCategoryList($cargo,'misc')
			);
		}
		
		$convenience = $this->confModelEm->getEquipment($data['version'],'O',null,null,'option_id','Convenience')->fetchAll();
		if($convenience){
			$miscList[] = array(
				'name' => 'Komfort',
				'type' => 'card',
				'list' => $this->getCategoryList($convenience,'misc')
			);
		}
		
		$emergency = $this->confModelEm->getEquipment($data['version'],'O',null,null,'option_id','Emergency')->fetchAll();
		if($emergency){
			$miscList[] = array(
				'name' => 'Notfall',
				'type' => 'card',
				'list' => $this->getCategoryList($emergency,'misc')
			);
		}
		
		$performance = $this->confModelEm->getEquipment($data['version'],'O',null,null,'option_id','Performance')->fetchAll();
		if($performance){
			$miscList[] = array(
				'name' => 'Fahrwerte/Leistung',
				'type' => 'card',
				'list' => $this->getCategoryList($performance,'misc')
			);
		}
		
		$seats = $this->confModelEm->getEquipment($data['version'],'O',null,null,'option_id','Seats')->fetchAll();
		if($seats){
			$miscList[] = array(
				'name' => 'Sitze',
				'type' => 'card',
				'list' => $this->getCategoryList($seats,'misc')
			);
		}
		
		$steering = $this->confModelEm->getEquipment($data['version'],'O',null,null,'option_id','Steering')->fetchAll();
		if($steering){
			$miscList[] = array(
				'name' => 'Lenkung',
				'type' => 'card',
				'list' => $this->getCategoryList($steering,'misc')
			);
		}
		
		$ventilation = $this->confModelEm->getEquipment($data['version'],'O',null,null,'option_id','Ventilation')->fetchAll();
		if($ventilation){
			$miscList[] = array(
				'name' => 'Belüftung',
				'type' => 'card',
				'list' => $this->getCategoryList($ventilation,'misc')
			);
		}
		
		$visibility = $this->confModelEm->getEquipment($data['version'],'O',null,null,'option_id','Visibility')->fetchAll();
		if($visibility){
			$miscList[] = array(
				'name' => 'Sicht',
				'type' => 'card',
				'list' => $this->getCategoryList($visibility,'misc')
			);
		}
		
		$miscList = array_unique($miscList, SORT_REGULAR);
		
		//redirect to next step if no data
		if(count($miscList) == 0){
			
			if($request->get('action') == 'back'){
				return $this->redirectToRoute('configurator_safety');
			}else{
				return $this->redirectToRoute('configurator_summary');
			}
		}
		
        return $this->render('config_misc/index.html.twig',
		[
			'car_media_o' => $carMedia,
			'temp_navigation' => $aside,
			'misc_list' => $miscList,
			'discount_price_opts' => $discountPrice,
			'item_list' => $itemList
		]);
    }
	
    public function location(Request $request)
    {
        if($request->get('misc') != null){
			
			$miscString = serialize($request->get('misc'));			
			$this->session->set('misc', $miscString);
		}
		
		$data = $this->getSessionData();		
		if($data['version'] == null){
			return $this->redirectToRoute('configurator_mark');
		}
		
		$form = $this->createForm(ConfiguratorLocationType::class, null);
        $form->handleRequest($request);

        if ($request->isMethod('POST') && $form->isSubmitted() && $form->isValid()) {
            /** @todo logic */
            return $this->redirectToRoute('configurator_dealer');
        }
		
		$carMedia = $this->getCarMedia($data);
		
        return $this->render('config_location/index.html.twig',
		[
			'car_media_o' => $carMedia,
            'form' => $form->createView(),
		]);
    }
	
	public function summary(Request $request)
    {
        
		//temporaly(without loacation)
		if($request->get('misc') != null){
			
			$miscString = serialize($request->get('misc'));
			$this->session->set('misc', $miscString);
		}
		
		$data = $this->getSessionData();
		
		$car = $this->confModelEm->getFilteredModels(array('mark','model_name','model_slug','doors','cabine','year','power','drive','fuel','version','variant_name','seats','gear','engine','jato_vehicle_id'),array('jato_vehicle_id'=>$data['version']),array('cargo','co_emission','energy_class'))->fetch();
		
		$image = $this->confModelEm->getImageByModelAndBody($data['model_slug'],$data['cabine']);
		
		$discountMax = $this->getDiscount(array('mark' => $data['mark'],'model' => $data['model_slug'],'body' => $data['cabine'],'version' => $data['version']),'MAX', array('amount_type' => 'P', 'main' => true),$data['group']);
		
		$discount = $this->getDiscountPrice($this->getItemList(null,$data,'sum'),$discountMax);
		
		$car = array(
			'src' => '/uploads/cars/'.$image.'.png',
			'alt' => $car['model_slug'],
			'name' => $car['mark'].' '.$car['version'],
			'model' => $car['model_name'],
			'valid_from' => date('d.m.y'),
			'valid_to' => date('d.m.y',strtotime('+8 days')),
			'discount' => number_format($discount['discount_from']/100,2,',','.').'%',
			'saved' => number_format($discount['saved'],2,',','.').'.–',
			'price' => number_format($discount['price'],2,',','.'),
			'power' => $car['power'].' PS',
			'drive' => $this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['drive'], $car['drive']),
			'fuel' => ucfirst($this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['fuel'], $car['fuel'])),
			'gear' => ucfirst($this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['gear'], $car['gear'])),
			'weight' => $this->confModelEm->getOptionValue($data['version'],0,'weight_2').' kg',
			'engine' => $car['fuel'] == 'E' ? '-' : $car['engine'].' l',
			'doors' => $car['doors'],
			'consumption1' => $this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['consumption_city'],'').': '.$this->confModelEm->getOptionValue($car['jato_vehicle_id'],0,'consumption_city'),
			'consumption2' => $this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['consumption_country'],'').': '.$this->confModelEm->getOptionValue($car['jato_vehicle_id'],0,'consumption_country'),
			'consumption3' => ucfirst($this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['consumption_average'],'').': '.$this->confModelEm->getOptionValue($car['jato_vehicle_id'],0,'consumption_average')),
			'energy_class' => array(substr($car['energy_class'],23))
		);
		
		$options = array_merge(array($data['color']),array($data['polster']),$data['packet'],$data['exterior'],$data['audio'],$data['safety'],$data['misc']);
		foreach($options as $option)
		{
			$optionData = $this->confModelEm->getOption($data['version'],$option);
			
			$car['equipments'][] = $optionData['name'];
		}
		
		return $this->render('config_summary/index.html.twig',
		[
			'car' => $car
		]);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function summaryPdf(Request $request)
    {
        ini_set('max_execution_time', 3600);
        $data = $this->getSessionData();

        $car = $this->confModelEm->getFilteredModels(array('mark','model_name','model_slug','doors','cabine','year','power','drive','fuel','version','variant_name','seats','gear','engine','jato_vehicle_id'),array('jato_vehicle_id'=>$data['version']),array('weight','consumption','cargo','co_emission','energy_class'))->fetch();
		$image = $this->confModelEm->getImageByModelAndBody($data['model_slug'],$data['cabine']);
		
        $discountMax = $this->getDiscount(array('mark' => $data['mark'],'model' => $data['model_slug'],'body' => $data['cabine'],'version' => $data['version']),'MAX', array('amount_type' => 'P', 'main' => true),$data['group']);

        $discount = $this->getDiscountPrice($this->getItemList(null,$data,'sum'),$discountMax);
        $today = new \DateTime();
        $fileName = $car['mark'].'_'.$car['model_name'].'_Konfiguration_'.$today->format('Ymd_His').'.pdf';

        $car = array(
            'src' => '/uploads/cars/'.$image.'.png',
            'alt' => $car['model_slug'],
            'name' => $car['mark'].' '.$car['version'],
            'model' => $car['model_name'],
            'valid_from' => date('d.m.y'),
            'valid_to' => date('d.m.y',strtotime('+8 days')),
            'discount' => number_format($discount['discount_from']/100,2,',','.').'%',
            'saved' => number_format($discount['saved'],2,',','.').'.–',
            'price' => number_format($discount['price'],2,',','.'),
            'power' => $car['power'].' PS',
            'drive' => $this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['drive'], $car['drive']),
            'fuel' => ucfirst($this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['fuel'], $car['fuel'])),
            'gear' => ucfirst($this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['gear'], $car['gear'])),
            'weight' => substr($car['weight'],41,-99).' kg',
            'engine' => $car['engine'].' l',
            'doors' => $car['doors'],
            'consumption1' => $this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['consumption_city'],'').': '.$this->confModelEm->getOptionValue($car['jato_vehicle_id'],0,'consumption_city'),
            'consumption2' => $this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['consumption_country'],'').': '.$this->confModelEm->getOptionValue($car['jato_vehicle_id'],0,'consumption_country'),
            'consumption3' => ucfirst($this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['consumption_average'],'').': '.$this->confModelEm->getOptionValue($car['jato_vehicle_id'],0,'consumption_average')),
            'energy_class' => array(substr($car['energy_class'],23))
        );

        $options = array_merge(array($data['color']),array($data['polster']),$data['packet'],$data['exterior'],$data['audio'],$data['safety'],$data['misc']);
        foreach($options as $option) {
            $optionData = $this->confModelEm->getOption($data['version'],$option);

            $car['equipments'][] = $optionData['name'];
        }



        return $this->pdf->download($car, 'configuration_summary', $fileName);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function shareMail(Request $request)
    {
        $data = $this->getSessionData();
        $car = $this->confModelEm->getFilteredModels(array('mark','model_name','model_slug','doors','cabine','year','power','drive','fuel','version','variant_name','seats','gear','engine','jato_vehicle_id', 'variant'),array('jato_vehicle_id'=>$data['version']),array('weight','consumption','cargo','co_emission','energy_class'))->fetch();
		$image = $this->confModelEm->getImageByModelAndBody($data['model_slug'],$data['cabine']);
        $discountMax = $this->getDiscount(array('mark' => $data['mark'],'model' => $data['model_slug'],'body' => $data['cabine'],'version' => $data['version']),'MAX', array('amount_type' => 'P', 'main' => true),$data['group']);
        $discount = $this->getDiscountPrice($this->getItemList(null,$data,'sum'),$discountMax);
        $form = $this->createForm(ConfiguratorShareMailType::class, null);

        $car = array(
            'src' => '/uploads/cars/'.$image.'.png',
            'alt' => $car['model_slug'],
            'name' => $car['mark'].' '.$car['version'],
            'mark' => $car['mark'],
            'model' => $car['model_name'],
            'version' => $car['version'],
            'variant' => $car['variant'],
            'valid_from' => date('d.m.y'),
            'valid_to' => date('d.m.y',strtotime('+8 days')),
            'discount' => number_format($discount['discount_from']/100,2,',','.').'%',
            'saved' => number_format($discount['saved'],2,',','.').'.–',
            'price' => number_format($discount['price'],2,',','.'),
            'power' => $car['power'].' PS',
            'drive' => $this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['drive'], $car['drive']),
            'fuel' => ucfirst($this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['fuel'], $car['fuel'])),
            'gear' => ucfirst($this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['gear'], $car['gear'])),
            'weight' => substr($car['weight'],41,-99).' kg',
            'engine' => $car['engine'].' l',
            'doors' => $car['doors'],
            'consumption1' => $this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['consumption_city'],'').': '.$this->confModelEm->getOptionValue($car['jato_vehicle_id'],0,'consumption_city'),
            'consumption2' => $this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['consumption_country'],'').': '.$this->confModelEm->getOptionValue($car['jato_vehicle_id'],0,'consumption_country'),
            'consumption3' => ucfirst($this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['consumption_average'],'').': '.$this->confModelEm->getOptionValue($car['jato_vehicle_id'],0,'consumption_average')),
            'energy_class' => array(substr($car['energy_class'],23)),
            'doors_seats' => $car['seats'],
            'cabine' => $car['cabine'],
            'year' => $car['year'],
            'energy_efficiency' => $car['energy_class'],
            'co2_emission' => $car['co_emission'],
        );


        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $postData = $form->getData();

            $this->notifications->send(Notifications::TYPE_SHARE_MAIL, $postData['email_to'], array_merge($car, $postData));

            return $this->redirectToRoute('configurator_summary');
        }

        return $this->render('config_share/mail.html.twig', [
            'form' => $form->createView(),
            'car' => $car,
        ]);
    }
	
	public function dealer(Request $request)
    {
		
		$data = $this->getSessionData();
		
		if($data['version'] == null){
			return $this->redirectToRoute('configurator_mark');
		}

        if ($request->isMethod('POST') && $request->get('submit') == 'offer') {
            
			$em = $this->getDoctrine()->getManager();
			$offer = new Offer();
			
			$offer->setVersion($data['version']);
			$offer->setColor($data['color']);
			$offer->setPacket($data['packet']);
			$offer->setRim($data['rim']);
			$offer->setPolster($data['polster']);
			$offer->setExterior($data['exterior']);
			$offer->setAudio($data['audio']);
			$offer->setSafety($data['safety']);
			$offer->setMisc($data['misc']);
			
			$offer->setMainDiscount($request->get('main'));
			$offer->setPrice($request->get('price'));
			
			if(is_array($request->get('additional'))){
				foreach($request->get('additional') as $additional){
					
					$additionalDiscount = $this->getDoctrine()
					->getRepository(Discount::class)
					->find($additional);
					
					$offer->addAdditionalDiscount($additionalDiscount);
				}		
			}
			
			$em->persist($offer);
            $em->flush();
			
			$this->session->set('offer', $offer->GetId());
			
            return $this->redirectToRoute('configurator_personal_data');
        }
		
		$carMedia = $this->getCarMedia($data,false);
		
		$discounts = $this->getDiscount(array('mark' => $data['mark'],'model' => $data['model_slug'],'body' => $data['cabine'],'version' => $data['version']),'MAX', array('amount_type' => 'P', 'carneo_amount_type' => 'P', 'main' => true),$data['group'],false,'d.value,d.dealer');
		
		$n=0;		
		foreach($discounts as $discount)
		{
			$sum = $this->getItemList(null,$data,$mode='sum');
			$mainPrice = $sum-$sum*($discount->getValue()-$discount->getCarneoProvision())/100;
			
			$discountList[$n] = array(
				'main_discount' => array(
					'id' => $discount->getId(),
					'value' => $discount->getValue()-$discount->getCarneoProvision(),
					'price' => number_format($mainPrice, 2, ',', '.'),
					'real_price' => $mainPrice,
					'saved' => number_format(($discount->getValue()-$discount->getCarneoProvision())*$sum/100, 2, ',', '.'),
					'real_saved' => ($discount->getValue()-$discount->getCarneoProvision())*$sum/100
				),
				'dealer' => array(
					'name' => $discount->getDealer()->getName()
				),
				'delivery_time' => $discount->getDeliveryTime(),
				'comment' => $discount->getComment()
			);
			
			$additionals = $this->getDiscount(array('mark' => $data['mark'],'model' => $data['model_slug'],'body' => $data['cabine'],'version' => $data['version']),'MAX', array('type' => 'R', 'main' => false, 'dealer' => $discount->getDealer()->getId()),$data['group'],false);
			
			if($additionals){
				foreach($additionals as $additional){
					
					if($additional->getAmountType() == 'P'){
						if($additional->getCarneoAmountType() == 'P'){
							$realPrice = ($additional->getValue()-$additional->getCarneoProvision())*$mainPrice/100;
							$value = $additional->getValue()-$additional->getCarneoProvision();
						}else{
							$realPrice = $additional->getValue()*$mainPrice/100-$additional->getCarneoProvision();
							$value = $additional->getValue();
						}
					}else{
						if($additional->getCarneoAmountType() == 'P'){
							$realPrice = $mainPrice-($additional->getValue()-($discount->getValue()*$additional->getCarneoProvision()/100));
							$value = $additional->getValue();
							
						}else{
							$realPrice = $mainPrice-($additional->getValue()-$additional->getCarneoProvision());
							$value = $additional->getValue()-$additional->getCarneoProvision();
						}
					}
					$price = number_format($realPrice, 2, ',', '.');
					
					$discountList[$n]['additional'][] = array(
						'id' => $additional->getId(),
						'name' => $additional->getFrontName(),
						'description' => $additional->getDescription(),
						'value' => $value.($additional->getAmountType() == 'P' ? '%' : ' €'),
						'real_value' => $value,
						'type' => $additional->getAmountType(),
						'price' => $price,
						'real_price' => $realPrice,
						'level' => $additional->getLevel(),
						'mark' => $additional->getMark(),
						'model' => $additional->getModel(),
						'version' => $additional->getVersion(),
						'body' => $additional->getBody()
					
					);
				}
			}
			
			$costs = $this->getDiscount(array('mark' => $data['mark'],'model' => $data['model_slug'],'body' => $data['cabine'],'version' => $data['version']),'MAX', array('type' => 'C', 'amount_type' => 'Q', 'main' => false, 'dealer' => $discount->getDealer()->getId()),$data['group'],false);
			
			if($costs){
				foreach($costs as $cost){
					
					$discountList[$n]['cost'][] = array(
						'id' => $cost->getId(),
						'name' => $cost->getFrontName(),
						'description' => $cost->getDescription(),
						'obligatory' => $cost->getObligatory(),
						'value' => $cost->getValue(),
						'price' => number_format($sum-$sum*($discount->getValue()-$discount->getCarneoProvision())/100+$cost->getValue(), 2, ',', '.'),
						'level' => $cost->getLevel(),
						'mark' => $cost->getMark(),
						'model' => $cost->getModel(),
						'version' => $cost->getVersion(),
						'body' => $cost->getBody()
					);
				}
			}
			$n++;
			
			
		}		
		
        return $this->render('config_dealer/index.html.twig',
		[
			'car_media_o' => $carMedia,
			'discount_list' => $discountList,
			'car_data' => $data
		]);
    }
	
	public function personalData($stock_id, Request $request)
    {
		
		$data = $this->getSessionData();	
		
		if($data['version'] == null && $stock_id == null){
			return $this->redirectToRoute('configurator_mark');
		}
		
		//check if stock or configurator
		if($stock_id == null){
			
			$carMedia = $this->getCarMedia($data,false);
		}else{
			
			$carMedia = $this->getStockMedia($stock_id);
		}
		
		$formUserLogin = $this->createForm(UserLoginType::class,null);
		$formUserLogin->handleRequest($request);
				
		$em = $this->getDoctrine()->getManager();
		
		//logged check		
		$token = unserialize($this->session->get('_security_account'));
		if($token){				
			$userToken = $token->getUser();
			$user = $this->getDoctrine()
					->getRepository(User::class)
					->find($userToken->getId());
						
			if($user && in_array('ROLE_REGISTER_USER',$user->getRoles())) {
				
					$logged = true;
			}
		}else{
			
			$logged = false;
			$user = new User();
		}		
		
		$formUserRegister = $this->createForm(UserRegisterType::class, $user);
		$formUserRegister->handleRequest($request);
				
		$error = '';

		if($formUserRegister->isSubmitted() && $formUserRegister->isValid()){
		
			//check if stock or configurator
			if($stock_id != null){
				
				$stock = $this->getDoctrine()->getRepository(Stock::class)->find($stock_id);			
				
				//save offer for stock
				$em = $this->getDoctrine()->getManager();
				$offer = new Offer();
				
				$offer->setPrice($stock->getPrice());
				$offer->setStock($stock);
				
				$em->persist($offer);
				$em->flush();
				
				$this->session->set('offer', $offer->GetId());
			}
			
			$offer = $this->getDoctrine()
				->getRepository(Offer::class)
				->find($this->session->get('offer'));	
					
			$formData = $request->request->all();
			
			if($logged){
					
				$route = 'configurator_thanks_login';
					
			}else{
					
				$user = $formUserRegister->getData();
				$user->setRoles(['ROLE_GUEST']);
				$em->persist($user);
				$em->flush();

                $this->session->set('user', $user->getId());
					
				$route = 'configurator_thanks_without_register';
			}
						
			$offer->setUser($user);
			$offer->setCallBack($formData['user_register']['call_back']);
			$offer->setFinancing($formData['user_register']['financing']);
			$offer->setComment($formData['user_register']['comment']);
			
			if($formData['user_register']['financing'] == 2 || $formData['user_register']['financing'] == 3){
				
				if($formData['user_register']['financing'] == 2){
					
					$financial_options['finance_time'] = $formData['user_register']['finance_time'];
					$financial_options['finance_deposit'] = $formData['user_register']['finance_deposit'];
					$financial_options['finance_rate'] = $formData['user_register']['finance_rate'];
					if(isset($formData['user_register']['finance_rate_value']))
						$financial_options['finance_rate_value'] = $formData['user_register']['finance_rate_value'];
				
				}else{
					
					$financial_options['leasing_mileage'] = $formData['user_register']['leasing_mileage'];
					$financial_options['leasing_time'] = $formData['user_register']['leasing_time'];
					$financial_options['leasing_payment'] = $formData['user_register']['leasing_payment'];
				}
				
				$offer->setFinancialOptions(serialize($financial_options));
			}
			
			$em->persist($offer);	
			$em->flush();

			//TO DO - email for stock offer
			if($offer->getStock() == null){
				$this->sendUserNotification($user,$offer);
				$this->sendAdminNotification($offer);
			}
				
			return $this->redirectToRoute($route);
		}
			
		return $this->render('config_personal_data/index.html.twig', [
			'car_media_o' => $carMedia,
			'formUserLogin' => $formUserLogin->createView(),
			'formUserRegister' => $formUserRegister->createView(),
			'logged' => $logged
		]);
	}
	
	public function thanksRegister()
    {
		$data = $this->getSessionData();	
		
		if(!$this->session->get('offer') || !$this->session->get('user')){
			return $this->redirectToRoute('configurator_mark');
		}
		
		$carMedia = $this->getCarMedia($data,false);
		$this->clearSession();
		
		return $this->render('config_thanks/user_registration.html.twig', [
			'car_media_o' => $carMedia
		]);
	}
	
	public function thanks(Request $request)
    {
		$data = $this->getSessionData();
		$carMedia = $this->getCarMedia($data,false);
		
        $form = $this->createForm(AccountThanksPasswordType::class, null);
		
		$form->handleRequest($request);				
		$em = $this->getDoctrine()->getManager();
 
		if($form->isSubmitted() && $form->isValid()){
			
			$formData = $request->request->all();	
			
			$account = $this->getDoctrine()
				->getRepository(User::class)
				->find($this->session->get('user'));
					
			$account->setPassword($this->encoder->encodePassword($account, $formData['account_thanks_password']['password']['first']))
				->setRoles(['ROLE_REGISTER_USER']);
			$account->setEnabled(true);
			$em->persist($account);
			$em->flush();
			
			return $this->redirectToRoute('configurator_thanks_register');
		}
		
		return $this->render('config_thanks/without_registration.html.twig', [
            'form' => $form->createView(),
			'car_media_o' => $carMedia
        ]);
    }
	
	public function thanksLogin()
    {
        $data = $this->getSessionData();
		$carMedia = $this->getCarMedia($data,false);
		
		$this->clearSession();
		
		return $this->render('config_thanks/user_login.html.twig', [
			'car_media_o' => $carMedia
		]);
    }
	
	private function getCategoryList($categoryList,$entity)
	{
		$data = $this->getSessionData();
		$n=0;
		$list = array();
		foreach($categoryList as $item)
		{			
			
			$descriptionList = $this->confModelEm->getOptionDescription($data['version'],$item['option_id']);
			$description = '<ul>';
			foreach($descriptionList as $dlist)
			{
				$description .= '<li>'.$dlist['description'].'</li>';
			}
			$description .= '</ul>';
			
			$list[$n] = array(
				'name' => $item['name'],
				'card_id' => $item['option_id'],
				'price_add' => $item['price'] == 0 ? '0.00' : $this->calculatePrice($item['price']),
				'description' => $description				
			);
			
			$options = array_merge($data['packet'],$data['exterior'],$data['audio'],$data['safety'],$data['misc']);			
			$includes = $this->checkIncludes($options,$item['option_id'],'checkbox',$entity.'[]');	
			$list[$n] = array_merge($list[$n],$includes['response']);	
			
			$n++;
		}
		
		return $list;
	}
	
	private function sendUserNotification($user, $offer)
	{
	    $entityManager = $this->getDoctrine()->getManager();

	    $mailQueue = new MailQueue();
	    $mailQueue->setOffer($offer);
	    $mailQueue->setType(Notifications::TYPE_FINISH_CONFIGURATION);

	    $entityManager->persist($mailQueue);
	    $entityManager->flush();
	}

    /**
	* Prepare data and send to administrator
	*
	* @param Offer $offer
	*/
	private function sendAdminNotification(Offer $offer) : void
	{
		$entityManager = $this->getDoctrine()->getManager();

		$mailQueue = new MailQueue();
		$mailQueue->setOffer($offer);
		$mailQueue->setType(Notifications::TYPE_ADMIN_FINISH_CONFIGURATION);

		$entityManager->persist($mailQueue);
		$entityManager->flush();
	}
	
	public function ajaxCheckBuild(Request $request)
    {
		$option_id = $request->get('option_id');
		$checked = $request->get('checked');
		$vehicle_id = $this->session->get('version');
		$ruleList = array();
		
		//excluded
		$optionRemove = $this->confModelEm->getOption($vehicle_id,$option_id);
		$build = $this->confModelEm->getOptionBuild($vehicle_id,$option_id,100007);
		foreach($build as $rule)
		{
			$ruleArray = $this->parseRule($rule['option_rule']);
			
			foreach($ruleArray as $optionId)
			{
				$option = $this->confModelEm->getOption($vehicle_id,$optionId);
				
				if($checked == 'false'){
					
					$descriptionList = $this->confModelEm->getOptionDescription($vehicle_id,$optionId);
					foreach($descriptionList as $item)
					{
						$optionDescription[] = $item['description'];
					}
					
					$ruleList['excluded'][$optionId] = $this->render('config_ajax/ajax_option.html.twig', [
						'option_id' => $optionId,
						'option_name' => $option['name'],
						'option_price' => $option['price'],
						'option_description' => $optionDescription
					])->getContent();
				}else{
									
					$ruleList['excluded'][$optionId] = $this->render('config_ajax/ajax_option_exclude.html.twig', [
						'option_id' => $optionId,
						'option_name' => $option['name'],
						'option_price' => $option['price'],
						'option_description' => $this->confModelEm->getOptionDescription($vehicle_id,$optionId),
						'option_description2' => $this->confModelEm->getOptionDescription($vehicle_id,$optionId),
						'remove_id' => $option_id,
						'remove_name' => $optionRemove['name'],
						'remove_price' => $optionRemove['price']
					])->getContent();
				}
				$ruleList['checked'][$optionId] = $checked;
			}			
		}
		
		//included
		$build = $this->confModelEm->getOptionBuild($vehicle_id,$option_id,100003);
		foreach($build as $rule)
		{
			$ruleArray = $this->parseRule($rule['option_rule']);
			
			foreach($ruleArray as $optionId)
			{
				$ruleList['included'][] = $optionId;
			}
		}
		
		return new JsonResponse($ruleList); 
	}
	
	public function ajaxLogin(Request $request)
	{
		
		$formData = $request->request->all();
		
		$email = $formData['user_login']['email'];
		$password = $formData['user_login']['password'];
		
		$account = $this->getDoctrine()
			->getRepository(User::class)
			->findOneBy(['email' => $email]);
					
		if($account && $this->encoder->isPasswordValid($account, $password)){
					
			$token = new UsernamePasswordToken($account, null, 'account', $account->getRoles());
					
			$this->get('security.token_storage')->setToken($token);
			$this->session->set('_security_account', serialize($token));
					
			$event = new InteractiveLoginEvent($request, $token);
					
			$this->dispatcher->dispatch("security.interactive_login", $event);
			$reply['status'] = 'OK';
			
			$reply['gender'] = $account->getGender();
			$reply['name'] = $account->getName();
			$reply['last_name'] = $account->getLastName();
			$reply['email'] = $account->getEmail();
			$reply['company'] = $account->getCompany();
			$reply['street'] = $account->getStreet();
			$reply['zip'] = $account->getZip();
			$reply['city'] = $account->getCity();
			$reply['phone'] = $account->getPhone();
			
		}else{
					
			$reply['status'] = 'ERROR';
		}
		
		return new JsonResponse($reply);
			
	}
	
	private function parseRule($rule)
	{
		$ruleList = array();
		
		if(strstr($rule,'AND')){
			$ruleArray = explode(' AND ',$rule);
			
			foreach($ruleArray as $rule2)
			{
				if(!strstr($rule2,'OR'))
					$ruleList[] = trim($rule2,'{}');
			}
		}elseif(!strstr($rule,'AND') && !strstr($rule,'OR')){
			$ruleList[] = trim($rule,'{}');
		}
		return $ruleList;
	}
	
	private function checkIncludes($options,$option_id,$type,$name,$modeAjax=true,$modeCheck=true)
	{
		$vehicle_id = $this->session->get('version');
		$list = array();
		foreach($options as $option)
		{
			if($option){
				$build = $this->confModelEm->getOptionBuild($vehicle_id,$option,100003); 
				foreach($build as $rule)
				{ 
					$rule = $this->parseRule($rule['option_rule']);
					if(in_array($option_id,$rule)){
						$list['response']['is_included'] = true;
						$list['disabled'] = false;
						return $list;
					} 
				}
				
				if($modeCheck == true){
					//excludes
					$build = $this->confModelEm->getOptionBuild($vehicle_id,$option,100007);
					$removed = array();
					$n = 1;
					foreach($build as $rule)
					{ 
						$rule = $this->parseRule($rule['option_rule']);
						if(in_array($option_id,$rule)){
							
							if(!in_array($option,$removed)){
								$removeValue = $this->confModelEm->getOption($vehicle_id,$option); 					
								$list['response']['deps']['remove'][] = array(
									'name' => $removeValue['name'],
									'card_id' => 'card_remove_'.$option,
									'is_removed' => true,
									'input' => array(
										'name' => 'remove[]',
										'id' => 'remove_'.$n,
										'type' => 'hidden',
										'value' => $option,
										'disabled' => true
									),
									'price_add' => '-'.$removeValue['price'],
									//'description' => '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>',
									'mod' => 'card-car-feature'
								);
								$removed[] = $option;
							}
							if(!isset($list['response']['deps']['add'])){
								$addValue = $this->confModelEm->getOption($vehicle_id,$option_id); 
								$list['response']['deps']['add'][] = array(
									'name' => $addValue['name'],
									'card_id' => 'card_add_'.$option_id,
									'is_added' => true,
									'input' => array(
										'name' => 'add',
										'id' => 'add_'.$n,
										'type' => 'hidden',
										'value' => $option_id,
										'disabled' => true
									),
									'price_add' => $addValue['price'] == 0 ? '0.00' : $addValue['price'],
									//'description' => $description,
									'disabled' => true,
									'mod' => 'card-car-feature'
								); 
							}
							$n++;
						} 
					}
				}
			}
		}
		
		$list['response']['input'] = array( 
			'name' => $name,
			'id' => $option_id,
			'type' => $type,
			'displaytype' => 'checkbox',
			'value' => $option_id
		);
		
		if(isset($list['response']['deps'])){
			$list['response']['input']['type'] = $list['response']['input']['displaytype'] = 'hidden';
		}
		
		if(isset($list['response']['deps']) || $modeAjax == false){
			$list['response']['mod'] = 'card-car-feature card-form price-add';
		}else{
			$list['response']['mod'] = 'card-car-feature build-opt card-form price-add';				
		}
		
		if(in_array($option_id,$options) && !isset($list['response']['deps'])){
			
			$list['response']['input']['checked'] = true;
			$list['disabled'] = false;
		}
		
		return $list;
	}
	
	private function calculateOptions($add, $removes, $activeCategory)
	{
		$data = $this->getSessionData();
		
		$categories = array('packet','exterior','audio','safety','misc');
		
		foreach($categories as $category){
		
			foreach($removes as $remove)
			{
				if (isset($data[$category]) && $data[$category] != null) {
					
					if(($key = array_search($remove, $data[$category])) !== false){
						
						unset($data[$category][$key]);
						$this->session->set($category, serialize($data[$category]));
					}			
				}	
			}
		}
		
		if(is_array($data[$activeCategory])){
			
			if(!in_array($add,$data[$activeCategory])){
				$data[$activeCategory][] = $add;
				$this->session->set($activeCategory, serialize($data[$activeCategory]));
			}
		}else{
			
			$this->session->set($activeCategory, $add);			
		}
	}
	
	private function getCarMedia($model,$model3d=true)
	{
		$text_subheading = '';
	
		$search = array('model_slug' => $model['model_slug']);
		
		if(isset($model['cabine']) && $model['cabine'] != null){
			$search['cabine'] = $model['cabine'];
		}
		
		if(isset($model['variant']) && $model['variant'] != null){
			
			if($model['variant'] == '-')
				$text_subheading .= 'STANDARD';
			else
				$text_subheading .= $model['variant'];
			
			$search['variant'] = $model['variant'];
		}
		
		if(isset($model['version']) && $model['version'] != null){
			
			$vehicle = $this->confModelEm->getFilteredModels(array('jato_vehicle_id','year','doors','model_name','version'),array('jato_vehicle_id' => $model['version']))->fetch();
			
			$car = $this->getImacaData($model['version']);
			
			$text_subheading = $model['variant'] == '-' ? 'STANDARD '.$vehicle['version'] : $vehicle['version'];

		}else{
			
			$vehicle = $this->confModelEm->getFilteredModels(array('jato_vehicle_id','year','doors','model_name'),$search,array(),null,'DE',true)->fetch();
			$car = $this->getImacaData($vehicle['jato_vehicle_id']);
				
		}
		
		$image = $this->confModelEm->getImageByModelAndBody($model['model_slug'],$model['cabine']);
		$text_heading = $model['mark'].' '.$vehicle['model_name'].(isset($model['cabine']) ? ' '.$this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['cabine'], $model['cabine'],'DE',true) : '');		
		
		$carMedia = array(
			'text_heading' => $text_heading,
			'text_subheading' => $text_subheading,
			'data_src_logo' => '/uploads/mark_logo/'.$vehicle['logo'],
			'alt_image' => $model['mark'].' '.$model['model_slug'],
			//'text_paragraph' => ''
		);
		
		if(isset($car->imgid) && isset($car->img) && $model3d == true){
			
			$carMedia['d_img'] = $car->imgid;
			if(isset($model['color']) && $model['color'] != null){
			
				$colors = $this->getColors($vehicle['jato_vehicle_id']);
				$colorCode = $this->confModelEm->getOption($vehicle['jato_vehicle_id'],$model['color']);				
				$carMedia['color'] = $colors[$colorCode['code']];
			}else{
				$carMedia['color'] = '#fff';
			}
		}else{
			
			$carMedia['data_src_image'] = '/uploads/cars/'.$image.'.png';
		}
		
		return $carMedia;		
	}
	
	private function getStockMedia($stock_id)
	{
		
		$stock = $this->getDoctrine()->getRepository(Stock::class)->find($stock_id);
		
		$car = $this->confModelEm->getFilteredModels(array('mark'),array('mark' => $stock->getMark()))->fetch();
		
		$carMedia = array(
			'text_heading' => $stock->getName(),
			'text_subheading' => $stock->getBody(),
			'data_src_logo' => '/uploads/mark_logo/'.$car['logo'],
			'alt_image' => $stock->getMark(),
			'data_src_image' => '/uploads/cars/'.$stock->getImage().'.png'
		);
		
		return $carMedia;		
	}
	
	private function getColors($vehicle)
	{
		$imacaColors = $this->getImacaData($vehicle);
		
		foreach($imacaColors->colors as $key => $value)
		{
			$colors[$key] = '#'.$value->hex;	
		}
		
		return $colors;
	}
	
	private function getDiscount($vehicles, $scale, $filter = null, $group = null, $one = true, $groupBy = null)
	{	
		$discount = $this->discountRepository->findDiscountRange($vehicles, $scale, $filter, $group, $one, $groupBy);
		
		if($discount == false && isset($vehicles['version'])){		
			
			unset($vehicles['version']);
			$discount = $this->discountRepository->findDiscountRange($vehicles, $scale, $filter, $group, $one, $groupBy);
		}
		if($discount == false && isset($vehicles['body'])){
				
			unset($vehicles['body']);
			$discount = $this->discountRepository->findDiscountRange($vehicles, $scale, $filter, $group, $one, $groupBy);			
		}
		if($discount == false && isset($vehicles['model'])){
				
			unset($vehicles['model']);
			$discount = $this->discountRepository->findDiscountRange($vehicles, $scale, $filter, $group, $one, $groupBy);				
		}	
		
		return $discount;			
	}
	
	private function getDiscountPrice($price,$discount,$discountText='Rabatt')
	{
		$discountPrice = array(
			'discount_text' => $discountText,
			'discount_from' => $discount['value'] ? ($discount['value']-$discount['provision'])*100 : '0.00',
			'price' => $discount['value'] ? $this->calculatePrice($price,$discount) : $this->calculatePrice($price),
			'saved' => $discount['value'] ? ($discount['value']-$discount['provision'])*$price/100 : '0.00',
		);
		return $discountPrice;
	}
	
	private function getItemList($discount,$data,$mode='list')
	{
		$itemList = array();
		if(!isset($data['variant']))
			return $itemList;
		
		$sum = 0;
		if($data['version'] && $data['version'] != null)
			$search = array('jato_vehicle_id' => $data['version']);
		else
			$search = array('model_slug' => $data['model_slug'], 'cabine' => $data['cabine'], 'variant' => $data['variant']);
		
		$variant = $this->confModelEm->getFilteredModels(array('M_price','variant_name'),$search)->fetch();
		$variant['name'] = $variant['variant_name'];
		if($mode == 'list'){
			$itemList[] = $this->addToItemList($variant,'Variant: ',$discount);
		}else{
			$sum += $variant['price'];
		}
		
		foreach($data as $key => $value)
		{
			if(is_array($value)){
				foreach($value as $val)
				{
					$item = $this->confModelEm->getOption($data['version'],$val);
					if($item){
						if($mode == 'list'){
							$itemList[] = $this->addToItemList($item,'',$discount);
						}else{
							$sum += $item['price'];
						}
					}
				}
			}else{
				if($key == 'rim' && $value == 1){
					$itemList[] = array(
						'key' => 'Felgen: Standard felgen',
						'value_1' => 0,
						'value_2' => 0
					);
				}else{
					switch($key){						
						case 'color':
						$name = 'Lackierung: ';
						break;
						
						case 'rim':
						$name = 'Felgen: ';
						break;
						
						case 'polster':
						$name = 'Polster: ';
						break;
						
						default:
						$name = '';
						break;
					}
					
					$item = $this->confModelEm->getOption($data['version'],intVal($value));
					if($item){
						
						if($key == 'color' || $key == 'polster'){
						
							$extended = $this->confModelEm->getOptionBuild($data['version'],$value,100006)->fetch();
							$rule = $this->parseRule($extended['option_rule']);
			
							if(count($rule) > 0 && $rule[0] > 0){				
								$object = $this->confModelEm->getOption($data['version'],$rule[0]);
								$item['price'] = $object['price'];
							}
						}
						
						if($mode == 'list'){
							$itemList[] = $this->addToItemList($item,$name,$discount);
						}else{
							$sum += $item['price'];
						}
					}
				}
			}
		}
		if($mode == 'list'){
			return $itemList;
		}else{
			return $sum;
		}
	}

	private function addToItemList($item,$name,$discount)
	{
		
		$itemArray = array(
			'key' => $name.($item['name'] == '-' ? 'Standard' : $item['name']),
			'value_1' => $this->calculatePrice($item['price'],$discount),
			'value_2' => $this->calculatePrice($item['price']),
		);
		
		if(isset($item['option_id']))
			$itemArray['value_3'] = $item['option_id'];
		
		return $itemArray;
	}
	
	private function calculatePrice($price,$discount=null)
	{
		if($discount == null){
			$finalPrice = $price;
		}else{
			$finalPrice = $price-$price*($discount['value']-$discount['provision'])/100;
		}
		return $finalPrice;		
	}
	
	public function getSessionData()
	{
		$data = array(
			'mark' => $this->session->get('mark'),
			'model_slug' => $this->session->get('model_slug'),
			'cabine' => $this->session->get('cabine'),
			'group' => $this->session->get('group'),
			'variant' => $this->session->get('variant'),
			'version' => $this->session->get('version'),
			'color' => $this->session->get('color'),
			'packet' => $this->session->get('packet') == null ? array() : unserialize($this->session->get('packet')),
			'rim' => $this->session->get('rim'),
			'polster' => $this->session->get('polster'),
			'exterior' => $this->session->get('exterior') == null ? array() : unserialize($this->session->get('exterior')),
			'audio' => $this->session->get('audio')== null ? array() : unserialize($this->session->get('audio')),
			'safety' => $this->session->get('safety')== null ? array() : unserialize($this->session->get('safety')),
			'misc' => $this->session->get('misc')== null ? array() : unserialize($this->session->get('misc')),
		);
		return $data;		
	}
	
	private function getAside($data)
	{
		$aside = array(
			array(
				'href' => '/configurator/mark',
				'content' => 'Marken',
				'active' => 'mark'
			),
			array(
				'href' => '/configurator/model/'.((isset($data['mark']) && $data['mark'] != null) ? $data['mark'] : ''),
				'content' => 'Modelle',
				'active' => 'model',
				'disabled' => (isset($data['mark']) && $data['mark'] != null) ? false : true,
			),
			array(
				'href' => '/configurator/car-detail/'.((isset($data['mark']) && $data['mark'] != null) ? $data['mark'] : '').'/'.((isset($data['model_slug']) && $data['model_slug'] != null) ? $data['model_slug'] : '').'/'.((isset($data['cabine']) && $data['cabine'] != null) ? $data['cabine'] : ''),
				'content' => 'Technische Details',
				'active' => 'car-details',
				'disabled' => (isset($data['cabine']) && $data['cabine'] != null) ? false : true,
			),
			array(
				'href' => '/configurator/variants/'.((isset($data['group']) && $data['group'] != null) ? $data['group'] : ''),
				'content' => 'Varianten',
				'active' => 'variants',
				'disabled' => (isset($data['variant']) && $data['variant'] != null) ? false : true,
			),
			array(
				'href' => '/configurator/engine',
				'content' => 'Motor & Getriebe',
				'active' => 'engine',
				'disabled' => (isset($data['version']) && $data['version'] != null) ? false : true,
			),
			array(
				'href' => '/configurator/color',
				'content' => 'Lackierung',
				'active' => 'color',
				'disabled' => (isset($data['color']) && $data['color'] != null) ? false : true,
			),
			array(
				'href' => '/configurator/package',
				'content' => 'Pakete & Komfort',
				'active' => 'package',
				'disabled' => (isset($data['packet']) && $data['packet'] != null) ? false : true,
			),
			array(
				'href' => '/configurator/rims',
				'content' => 'Felgen',
				'active' => 'rims',
				'disabled' => (isset($data['rim']) && $data['rim'] != null) ? false : true,
			),
			array(
				'href' => '/configurator/interior',
				'content' => 'Interior',
				'active' => 'interior',
				'disabled' => (isset($data['polster']) && $data['polster'] != null) ? false : true,
			),
			array(
				'href' => '/configurator/exterior',
				'content' => 'Exterieur',
				'active' => 'exterior',
				'disabled' => (isset($data['exterior']) && $data['exterior'] != null) ? false : true,
			),
			array(
				'href' => '/configurator/multimedia',
				'content' => 'Multimedia',
				'active' => 'multimedia',
				'disabled' => (isset($data['audio']) && $data['audio'] != null) ? false : true,
			),
			array(
				'href' => '/configurator/safety',
				'content' => 'Sicherheit',
				'active' => 'safety',
				'disabled' => (isset($data['safety']) && $data['safety'] != null) ? false : true,
			),
			array(
				'href' => '/configurator/misc',
				'content' => 'Weiteres',
				'active' => 'misc',
				'disabled' => (isset($data['misc']) && $data['misc'] != null) ? false : true,
			)
		);
		
		return $aside;		
	}
}
