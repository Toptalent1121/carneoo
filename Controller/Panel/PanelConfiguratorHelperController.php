<?php

namespace App\Controller\Panel;

use App\Repository\ConfiguratorModelRepository;
use App\Repository\DiscountRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Stock;
use App\Entity\Dealer;
use App\Entity\Discount;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use App\Form\Panel\StockImportType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;

class PanelConfiguratorHelperController extends AbstractController
{
	
	/**
     * @param ConfiguratorModelRepository $confModelEm
     *
     * @return void
     */
	public function checkImages(ConfiguratorModelRepository $confModelEm)
	{
		
		ini_set('max_execution_time', 0);

        $fields = ['mark','model','karosserie','jato_id','imgid','error'];
        $csvOutput = fopen("php://output",'w') or die("Can't open php://output");
        fputcsv($csvOutput, $fields);

        header("Content-Type:application/csv");
        header("Content-Disposition:attachment;filename=broken_models.csv");
		
		$models = $confModelEm->getFilteredModels(array('mark','model_name','jato_vehicle_id','cabine'),array());
		
		foreach($models as $model)
		{
			
			$options = array(
				CURLOPT_TIMEOUT =>  3600, 
				CURLOPT_URL     => 'https://imaca.de/json/get_img_v2.php?jatoid='.$model['jato_vehicle_id'],
			);
			$ch = curl_init();
			curl_setopt_array($ch, $options);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$output = curl_exec($ch); 
			curl_close($ch);

			$car = json_decode($output);
			
			if(!isset($car->imgid)){
				
				$data = [$model['mark'],$model['model_name'],$confModelEm->getSchemaDescription($confModelEm::$JATO_STANDARD_MAPPER['cabine'], $model['cabine']),$model['jato_vehicle_id'],'none','broken API - no imgid'];
				fputcsv($csvOutput, $data);
			}else{
				
				$finder = new Finder();				
				$finder->files()->name($car->imgid.'.png')->in($_SERVER['DOCUMENT_ROOT'].'/uploads/cars');
				
				if ($finder->hasResults() == false) {
					
					$data = [$model['mark'],$model['model_name'],$confModelEm->getSchemaDescription($confModelEm::$JATO_STANDARD_MAPPER['cabine'], $model['cabine']),$model['jato_vehicle_id'],$car->imgid,'no image file'];
					fputcsv($csvOutput, $data);
				}
			}
			
		}
		fclose($csvOutput) or die("Can't close php://output");
        exit;
	}
	
	/**
     * @param Request $request
     * @param ConfiguratorModelRepository $confModelEm
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
	public function importStock(Request $request, ConfiguratorModelRepository $confModelEm, DiscountRepository $discountRepository)
	{
		
		/* list of stock dealers with ID (temporary solution)
		6 - Auto Zentrum Nürnberg-Feser GmbH Audi
		36 - Auto Zentrum Nürnberg-Feser GmbH Skoda
		43 - Auto Zentrum Nürnberg-Feser GmbH VW Nutzfahrzeuge
		39 - Autohaus Kronenberger GmbH
		9 - Autohaus Michael Schmidt GmbH
		19 - Autohaus Stark GmbH
		28 - Autohaus Weiland GmbH
		3 - HN GmbH
		48 - Volkswagen Automobile Berlin GmbH
		*/
		
		
		ini_set('max_execution_time', 0);
        $form = $this->createForm(StockImportType::class, null);
        $form->handleRequest($request);
		$message = '';
		$noDiscount = array();

        if ($request->isMethod('POST') && $form->isSubmitted() && $form->isValid()) {
			try {
				
				$data = $request->request->all();
				$fileData = $request->files->get('stock_import');
				
				/**
				* @var UploadedFile $file
				*/
				$file = $fileData['file'];

				/* Create temporary direction */
				$this->createTemporaryFolderIfNotExists();

				$fileName = 'stock_' . rand(100, 200) . '.xml';
				$filePath = $this->getTemporaryFolderPath();

				$file->move($filePath, $fileName);
				
			} catch (\Exception $exception) {
                dump($exception);exit;
            }
			
			$em = $this->getDoctrine()->getManager();
			
			switch($data['stock_import']['dealer']){
				
				case 43:	
			
				$xml = simplexml_load_file($filePath.$fileName) or die("Error: Cannot create object");
				foreach($xml->children() as $car) 
				{
						
					$stock = new Stock();
					$stock->setNumber($car->Vehicle->VehicleNo[0]->__toString()); //offer number
					
					$name = explode(' ',$car->Vehicle->Description[0]->__toString());
					
					$stock->setName($name[0] == 'VW' ? $name[1].' '.$name[2] : $name[0].' '.$name[1]); //name
					
					$vehicle = $confModelEm->getFilteredModels(array('jato_vehicle_id','model_name','model_slug','gear','drive','doors','fuel','cabine','power'),array('mark' => $data['stock_import']['mark'], 'model_name' => $name[0]),array('energy_class'),'model_slug')->fetch();
					
					if(!$vehicle){
						$vehicle = $confModelEm->getFilteredModels(array('jato_vehicle_id','model_name','model_slug','gear','drive','doors','fuel','cabine','power'),array('mark' => $data['stock_import']['mark'], 'model_name' => $name[1]),array('energy_class'),'model_slug')->fetch();
					}
					
					$dealer = $this->getDoctrine()->getRepository(Dealer::class)->find($data['stock_import']['dealer']);
					
					$stock->setMark($data['stock_import']['mark']);
					$stock->setDealer($dealer);
					$stock->setArchive(0);
					
					if($vehicle['model_slug'] != null && $vehicle['cabine'] != null){
						$stock->setImage($confModelEm->getImageByModelAndBody($vehicle['model_slug'],$vehicle['cabine']));
					}
					
					$discount = $discountRepository->findDiscountRange(array('mark' => $data['stock_import']['mark'], 'model' => $vehicle['model_slug'],'amount_type' => 'P','dealer' => $dealer),'MAX');
					if($discount != false){
						$stock->setDiscount($discount['value']-$discount['provision']);
					}else{
						$noDiscount[] = $car->Vehicle->Description[0]->__toString();
						continue;
					}
					
					//if($vehicle['cabine'] != null){
					//	$stock->setBody(ucfirst($confModelEm->getSchemaDescription($confModelEm::$JATO_STANDARD_MAPPER['cabine'], $vehicle['cabine']))); 
					//}
					
					if($vehicle['drive'] != null){
						$stock->setDrive(ucfirst($confModelEm->getSchemaDescription($confModelEm::$JATO_STANDARD_MAPPER['drive'], $vehicle['drive']))); 
					}
					
					$stock->setPrice($car->Vehicle->PriceTotal['Value']->__toString()); //price
					
					$stock->setCapacity($car->Vehicle->Engine->Capacity['Value']->__toString()); //capacity
					
					if($vehicle['power'] != null){
						$stock->setPower($vehicle['power']); //power
					}
					
					if($car->Vehicle->Labeling->Weight['Value'] != null){
						$stock->setWeight($car->Vehicle->Labeling->Weight['Value']->__toString()); //weight
					}		
					
					if($car->Vehicle->Labeling->ConsumpEfficiency[0] != null){
						$stock->setEnergyClass($car->Vehicle->Labeling->ConsumpEfficiency[0]->__toString()); //energy_class
					}else{
						$stock->setEnergyClass(substr($vehicle['energy_class'],23));
					}
					
					$stock->setValidDate(new \DateTime($car->Vehicle->DateCreated[0]->__toString()));
					
					if($vehicle['doors'] != null){
						$stock->setDoors($vehicle['doors']); 
					}
					
					if($vehicle['gear'] != null){
						$stock->setGear(ucfirst($confModelEm->getSchemaDescription($confModelEm::$JATO_STANDARD_MAPPER['gear'], $vehicle['gear'])));
					}
					if($vehicle['fuel'] != null){
						$stock->setFuel(ucfirst($confModelEm->getSchemaDescription($confModelEm::$JATO_STANDARD_MAPPER['fuel'], $vehicle['fuel'])));
					}
					
					$consumptionArray = array();
					
					if(isset($car->Vehicle->Labeling->Consumptions->Consumption) && is_object($car->Vehicle->Labeling->Consumptions)){
					
						foreach($car->Vehicle->Labeling->Consumptions->Consumption as $consumption)
						{
							$consumptionArray[] = $consumption['Value']->__toString();
						}
						$stock->setConsumption($consumptionArray);
					}
					
					$optionArray = array();
					
					foreach($car->Vehicle->OptionList->Option as $option)
					{
						if($option['Type'] == '1'){
							$stock->setColor($option->Description[0]->__toString()); //color
						}else{
							$optionArray[] = $option->Description[0]->__toString(); 
						}
					}
					$stock->setOptions($optionArray);
					
					$em->persist($stock);
					$em->flush();
				}
				$message = 'File imported successfully';
				
				break;
				
				case 6:
				
				$sheetData = $this->readXLXSFile($filePath.$fileName);
				unset($sheetData[0]);
				
				foreach($sheetData as $car)
				{
					$stock = new Stock();
					$stock->setNumber($car[3]); //offer number
					
					$name = explode(' ',$car[5]);
					
					$stock->setName($name[0].' '.$name[1]); //name
					
					$vehicle = $confModelEm->getFilteredModels(array('jato_vehicle_id','model_name','model_slug','gear','drive','doors','power','cabine'),array('model_name' => $name[0]),array('energy_class'),'model_name')->fetch();
					
					$dealer = $this->getDoctrine()->getRepository(Dealer::class)->find($data['stock_import']['dealer']);
					
					$stock->setMark($data['stock_import']['mark']);
					$stock->setDealer($dealer);
					$stock->setArchive(0);
					
					if($vehicle['model_slug'] != null && $vehicle['cabine'] != null){
						$stock->setImage($confModelEm->getImageByModelAndBody($vehicle['model_slug'],$vehicle['cabine']));
					}
					
					$discount = $discountRepository->findDiscountRange(array('mark' => $data['stock_import']['mark'], 'model' => $vehicle['model_slug'],'amount_type' => 'P','dealer' => $dealer),'MAX');
					if($discount != false){
						$stock->setDiscount($discount['value']-$discount['provision']);
					}else{
						$noDiscount[] = $name[0].' '.$name[1];
						continue;
					}
					$stock->setBody($car[6]); 
					
					$stock->setDrive(ucfirst($confModelEm->getSchemaDescription($confModelEm::$JATO_STANDARD_MAPPER['drive'], $vehicle['drive']))); 
										
					$stock->setPrice(str_replace('.','',substr($car[11],0,-3))); //price
					
					$stock->setCapacity($confModelEm->getOptionValue($vehicle['jato_vehicle_id'],0,'capacity')); //capacity
					
					if($vehicle['power'] != null){
						$stock->setPower($vehicle['power']); //power
					}
					
					$stock->setWeight($confModelEm->getOptionValue($vehicle['jato_vehicle_id'],0,'weight_1')); //weight		
					$stock->setEnergyClass(substr($vehicle['energy_class'],23));

					$stock->setValidDate(new \DateTime(date('Y-m-d',strtotime('+ '.$car[32].' days'))));
					
					if($vehicle['doors'] != null){
						$stock->setDoors($vehicle['doors']);
					}
					
					$stock->setGear($car[15]);
					$stock->setFuel($car[13]);
					$stock->setColor($car[17]); //color
					
					$consumption = array(
						$confModelEm->getOptionValue($vehicle['jato_vehicle_id'],0,'consumption_city'),
						$confModelEm->getOptionValue($vehicle['jato_vehicle_id'],0,'consumption_country'),
						$confModelEm->getOptionValue($vehicle['jato_vehicle_id'],0,'consumption_average')
					);
					$stock->setConsumption($consumption);
					
					$options = explode(',',$car[30]);
					$stock->setOptions($options);
					
					$em->persist($stock);
					$em->flush();	
				}
				$message = 'File imported successfully';
				break;
				
				default:
				$message = 'Chosen dealer not implemented yet.';
			}
			
			/* Delete uploaded file*/
            $this->deleteFile($filePath.$fileName);
			
			if(count($noDiscount) > 0){
				$message .= '<br><br>Cannot find discounts for following vehicles:<br>';
				foreach($noDiscount as $no)
				{
					$message .= $no.'<br>';
				}
			}
		}
		
		return $this->render('panel/configurator/helper/import.html.twig', [
			'message' => $message,
            'form' => $form->createView(),
        ]);
	}
	
	/**
     * @param ConfiguratorModelRepository $confModelEm
     *
     * @return void
     */
	public function fixVersion(ConfiguratorModelRepository $confModelEm, DiscountRepository $discountRepository)
	{
		
		$em = $this->getDoctrine()->getManager();
		$discounts = $discountRepository->findAllChildrenByLevel('VERSION');
		
		foreach($discounts as $discount)
		{
			
			$version = $confModelEm->getFilteredModels(array('jato_vehicle_id','vehicle_id'),array('vehicle_id' => $discount->getVersion()))->fetch();
			
			if($version['jato_vehicle_id'] != null){				
				$discount->setVersion($version['jato_vehicle_id']);
				$em->persist($discount);
				$em->flush();
			}
		}
		
		return;
	}
	
	
	/**
     * @param string $filePath
     */
    private function deleteFile(string $filePath)
    {
        unlink($filePath);
    }

    /**
     * Create temporary direction if not exists
     */
    private function createTemporaryFolderIfNotExists()
    {
        if (false == file_exists($this->getTemporaryFolderPath())) {
            mkdir($this->getTemporaryFolderPath(), 0777, true);
        }
    }

    /**
     * Get temporary folder path
     *
     * @return string
     */
    private function getTemporaryFolderPath()
    {
        return $this->getParameter('kernel.project_dir') . '/var/tmp/';
    }
	
	/**
     * @param string $filePath
     *
     * @return array
     *
     * @throws Exception
     * @throws ReaderException
     */
    private function readXLXSFile(string $filePath)
    {
        $reader = new Xls();

        $spreadsheet = $reader->load($filePath);
        $data = $spreadsheet->getActiveSheet()->toArray();

        return $data;
    }
}