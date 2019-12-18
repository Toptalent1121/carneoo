<?php

namespace App\Controller\Panel;

use App\Controller\HelperController;
use App\Entity\Offer;
use App\Repository\ConfiguratorModelRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Datatable\Panel\ConfiguratorOfferDatatable;

/**
 * Class PanelConfiguratorOfferController
 *
 * @package App\Controller\Panel
 */
class PanelConfiguratorOfferController extends HelperController
{
    /**
     * @param Request $request
     * @param TranslatorInterface $translator
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function index(Request $request, TranslatorInterface $translator)
    {
        if (!$this->isGranted('offer_read')) {
            $flashbag = $this->get('session')->getFlashBag();
            $flashbag->add("danger", $translator->trans('form.messages.accessDenied', [], 'dealer'));
            return $this->redirectToRoute('panel_dashboard_index');
        }

        return $this->render('panel/configurator/offer/index.html.twig', []);
		
	}

    /**
     * @param Request $request
     * @param ConfiguratorOfferDatatable $datatable
     *
     * @return JsonResponse
     */
	public function list(Request $request, ConfiguratorOfferDatatable $datatable)
    {
        $data = $datatable->getData();

        return new JsonResponse($data, 200);
    }

    /**
     * @param Request $request
     * @param Offer $offer
     * @param ConfiguratorModelRepository $configuratorModelRepository
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function view(Request $request, Offer $offer, ConfiguratorModelRepository $configuratorModelRepository)
    {
        
		if($offer->getStock() == null){
			
			$data['color'] = $configuratorModelRepository->getOption($offer->getVersion(), $offer->getColor());
			
			if($offer->getRim() == 1){
				$data['rim'] = array('name' => 'Staqndard felgen', 'price' => 0);
			}else{
				$data['rim'] = $configuratorModelRepository->getOption($offer->getVersion(),$offer->getRim());
			}
			$data['polster'] = $configuratorModelRepository->getOption($offer->getVersion(), $offer->getPolster());
			$data['packet'] = $this->getOptions($configuratorModelRepository, $offer->getVersion(), $offer->getPacket());
			$data['exterior'] = $this->getOptions($configuratorModelRepository, $offer->getVersion(), $offer->getExterior());
			$data['audio'] = $this->getOptions($configuratorModelRepository, $offer->getVersion(), $offer->getAudio());
			$data['safety'] = $this->getOptions($configuratorModelRepository, $offer->getVersion(), $offer->getSafety());
			$data['misc'] = $this->getOptions($configuratorModelRepository, $offer->getVersion(), $offer->getMisc());
			
			$car = $configuratorModelRepository->getFilteredModels(array('mark','model_name','model_slug','doors','cabine','year','version','fuel','power'),array('jato_vehicle_id'=>$offer->getVersion()))->fetch();
			
			$image = $configuratorModelRepository->getImageByModelAndBody($car['model_slug'],$car['cabine']);
			$data['mark'] = $car['mark'];
			$data['model'] = $car['model_name'];
			$data['fuel'] = $configuratorModelRepository->getSchemaDescription($configuratorModelRepository::$JATO_STANDARD_MAPPER['fuel'],$car['fuel']);
			$data['power'] = $car['power'];
			
			$discount = $this->getDoctrine()->getRepository('App:Discount')->find($offer->getMainDiscount());
			$data['discount'] = $discount->getValue();
			$dealer = $discount->getDealer()->getName();
		}else{
			
			$stock = $offer->getStock();
			
			$data['color']['name'] = $stock->getColor();
			$image = $stock->getImage();
			$data['mark'] = $stock->getMark();
			$data['model'] = $stock->getName();
			$data['fuel'] = $stock->getFuel();
			$data['power'] = $stock->getPower();
			
			$dealer = $stock->getDealer()->getName();		
		}

        return $this->render('panel/configurator/offer/view.html.twig', [
            'offer' => $offer,
			'dealer' => $dealer,
            'carImage' => '/uploads/cars/'.$image.'.png',
            'data' => $data
        ]);
    }

    /**
     * @param ConfiguratorModelRepository $configuratorModelRepository
     * @param $version
     * @param $options
     * @return array
     */
    private function getOptions(ConfiguratorModelRepository $configuratorModelRepository, $version, $options)
    {
        $optionData = [];

        foreach ($options as $option) {
            $optionData[] = $configuratorModelRepository->getOption($version, $option);
        }

        return $optionData;
    }
}