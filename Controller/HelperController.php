<?php

namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\ConfiguratorModelRepository;

/**
 * Class HelperController
 *
 * @package App\Controller
 */
class HelperController extends AbstractController
{
	
	protected function getImacaData($vehicle_id)
	{
		//3D - ask imaca about car_id
		$options = array(
			CURLOPT_TIMEOUT =>  3600, 
			CURLOPT_URL     => 'https://imaca.de/json/get_img_v2.php?jatoid='.$vehicle_id,
		);
		$ch = curl_init();
		curl_setopt_array($ch, $options);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$output = curl_exec($ch); 
		curl_close($ch);

		$car = json_decode($output);
		
		if(isset($car->imgid) && $car->imgid != null){
			
			if(!file_exists($_SERVER['DOCUMENT_ROOT'].'/3d/resources/cars/'.$car->imgid.'/car2.json') && file_exists($_SERVER['DOCUMENT_ROOT'].'/3d/resources/cars/'.$car->imgid)){
				file_put_contents($_SERVER['DOCUMENT_ROOT'].'/3d/resources/cars/'.$car->imgid.'/car2.json', $output);
			}
		}
		
		return $car;		
	}
	
	protected function clearSession()
	{
		$this->session->remove('mark');
		$this->session->remove('model_slug');
		$this->session->remove('cabine');
		$this->session->remove('group');
		$this->session->remove('variant');
		$this->session->remove('version');
		$this->session->remove('color');
		$this->session->remove('packet');
		$this->session->remove('rim');
		$this->session->remove('polster');
		$this->session->remove('exterior');
		$this->session->remove('audio');
		$this->session->remove('safety');
		$this->session->remove('misc');
		$this->session->remove('offer');
	}
}