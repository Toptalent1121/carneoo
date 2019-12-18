<?php

namespace App\Controller\Panel;

use App\Entity\CarBestseller;
use App\Form\Panel\CarBestsellerType;
use App\Repository\CarBestsellerRepository;
use App\Repository\ConfiguratorMarkRepository;
use App\Repository\ConfiguratorModelRepository;
use App\Traits\Panel\JATOMapperTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class PanelCarBestsellerController
 *
 * @package App\Controller\Panel
 */
class PanelCarBestsellerController extends AbstractController
{
    /**
     * @param CarBestsellerRepository $carBestsellerRepository
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function index(CarBestsellerRepository $carBestsellerRepository, ConfiguratorModelRepository $configuratorModelRepository): Response
    {
        $carBestsellers = $carBestsellerRepository->getAll();

        return $this->render('panel/car_bestseller/index.html.twig', [
            'car_bestsellers' => $this->prepareCarBestsellersData($carBestsellers, $configuratorModelRepository),
        ]);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function add(Request $request, ConfiguratorModelRepository $configuratorModelRepository): Response
    {
        $carBestseller = new CarBestseller();
        $form = $this->createForm(CarBestsellerType::class, $carBestseller);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $request->request->all();
            $data = $data['car_bestseller'];
			
			$carBestseller->setMark($data['mark']);

            if(isset($data['version']) && $data['version'] != ''){
                $carBestseller->setVersion($data['version']);
				$carBestseller->setModel($data['model']);
				$carBestseller->setBody($data['body']);
				$criteria = ['vehicle_id' => $data['version']];
            }elseif(isset($data['body']) && $data['body'] != ''){
                $carBestseller->setBody($data['body']);
				$carBestseller->setModel($data['model']);
				$criteria = ['mark' => $data['mark'], 'model_slug' => $data['model'], 'cabine' => $data['body']];
            }elseif(isset($data['model']) && $data['model'] != ''){
                $carBestseller->setModel($data['model']);
				$criteria = ['mark' => $data['mark'], 'model_slug' => $data['model']];
            }		
			
			$price = $configuratorModelRepository->getMinPrice($criteria)->fetchColumn();
			$carBestseller->setMinPrice($price);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($carBestseller);
            $entityManager->flush();

            return $this->redirectToRoute('panel_car_bestseller_index');
        }

        return $this->render('panel/car_bestseller/add.html.twig', [
            'car_bestseller' => $carBestseller,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param Request $request
     * @param CarBestseller $carBestseller
     *
     * @return Response
     */
    public function edit(Request $request, CarBestseller $carBestseller, ConfiguratorModelRepository $configuratorModelRepository): Response
    {
        $form = $this->createForm(CarBestsellerType::class, $carBestseller);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $request->request->all();
            $data = $data['car_bestseller'];
			
			$carBestseller->setMark($data['mark']);
			
			if(isset($data['version']) && $data['version'] != ''){
                $carBestseller->setVersion($data['version']);
				$carBestseller->setModel($data['model']);
				$carBestseller->setBody($data['body']);
				$criteria = ['vehicle_id' => $data['version']];
            }elseif(isset($data['body']) && $data['body'] != ''){
                $carBestseller->setBody($data['body']);
				$carBestseller->setModel($data['model']);
				$criteria = ['mark' => $data['mark'], 'model_slug' => $data['model'], 'cabine' => $data['body']];
            }elseif(isset($data['model']) && $data['model'] != ''){
                $carBestseller->setModel($data['model']);
				$criteria = ['mark' => $data['mark'], 'model_slug' => $data['model']];
            }			
			
			$price = $configuratorModelRepository->getMinPrice($criteria)->fetchColumn();
			$carBestseller->setMinPrice($price);

            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('panel_car_bestseller_index', [
                'id' => $carBestseller->getId(),
            ]);
        }

        return $this->render('panel/car_bestseller/edit.html.twig', [
            'car_bestseller' => $carBestseller,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param Request $request
     * @param CarBestseller $carBestseller
     * @param TranslatorInterface $translator
     *
     * @return JsonResponse
     */
    public function delete(Request $request, CarBestseller $carBestseller, TranslatorInterface $translator): JsonResponse
    {
        if (empty($carBestseller)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.deleteFailed', [], 'widget').': '.$translator->trans('form.messages.homeBestsellerNotFound', [], 'car_bestseller'),
            ], 200);
        }

        if (!$this->isGranted('car_bestseller_delete', $carBestseller)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.deleteFailed', [], 'widget').': '.$translator->trans('form.messages.noPermission', [], 'car_bestseller'),
            ], 200);
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($carBestseller);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => $translator->trans('form.messages.deleteSuccess', [], 'car_bestseller'),
        ], 200);
    }

    /**
     * @param Request $request
     * @param ConfiguratorMarkRepository $markRespository
     * @param ConfiguratorModelRepository $modelRespository
     *
     * @return JsonResponse
     */
    public function vehicleList(Request $request, ConfiguratorMarkRepository $markRespository, ConfiguratorModelRepository $modelRespository)
    {
        $mark = $request->get('mark');
        $model = $request->get('model');
        $body = $request->get('body');

        $responseArray = [];

        if($mark) {
            $vehicles = $modelRespository->getFilteredModels(array('model_slug','model_name'),array('mark' => $mark),array(),'model_slug')->fetchAll();
            foreach($vehicles as $vehicle){
                $responseArray[] = array(
                    "vehicle_id" => $vehicle['model_slug'],
                    "name" => $vehicle['model_name']
                );
            }
        } elseif ($model) {
            $criteria = [
                'model_slug' => $model,
            ];

            if($body) {
                $criteria['cabine'] = $body;
            }
            $vehicles = $modelRespository->getFilteredModels(array('version','vehicle_id'),$criteria)->fetchAll();
            foreach($vehicles as $vehicle){
                $responseArray[] = array(
                    "vehicle_id" => $vehicle['vehicle_id'],
                    "name" => $vehicle['version']
                );
            }
        } elseif ($body) {
            $cabines = $modelRespository->getFilteredModels(array('cabine'),array('model_slug' => $body),array(),'cabine')->fetchAll();
            foreach($cabines as $cabine){
                $responseArray[] = array(
                    "vehicle_id" => $cabine['cabine'],
                    "name" => $modelRespository->getSchemaDescription($modelRespository::$JATO_STANDARD_MAPPER['cabine'], $cabine['cabine'])
                );
            }
        } else {
            $vehicles = $markRespository->getAllMarks();
            foreach($vehicles as $vehicle){
                $responseArray[] = array(
                    "vehicle_id" => $vehicle['name'],
                    "name" => $vehicle['name']
                );
            }
        }

        return new JsonResponse($responseArray);
    }

    /**
     * @param Request $request
     * @param TranslatorInterface $translator
     *
     * @return JsonResponse
     */
    public function order(Request $request, TranslatorInterface $translator)
    {
        try {
            $this->handleOrderChange($request, $translator);
        } catch (EntityNotFoundException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.orderFailed', [], 'widget').': '.$e->getMessage(),
            ], 200);
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'OK',
        ], 200);
    }

    /**
     * Saves checned Order into database
     *
     * @param Request $request Request
     * @param TranslatorInterface $translator Translator
     *
     * @throws EntityNotFoundException An exception is thrown in case of errors
     */
    private function handleOrderChange(Request $request, TranslatorInterface $translator)
    {
        $em = $this->getDoctrine()->getManager();
        $carBestsellerRepository = $em->getRepository('App\Entity\CarBestseller');
        $carBestsellerOrder = $request->request->get('car_bestseller');
        $a  = 1;

        foreach ($carBestsellerOrder as $details) {
            $carBestseller = $carBestsellerRepository->findOneBy([
                'id' => $details
            ]);

            if (empty($carBestseller)) {
                throw new EntityNotFoundException($translator->trans('form.messages.widgetNotFound', [], 'widget').' ID: '.$details);
            }

            $carBestseller->setCarBestsellerOrder($a);
            $em->persist($carBestseller);
            $a++;
        }
        $em->flush();
        $em->detach($carBestseller);
    }

    /**
     * @param array $carBestsellers
     * @param ConfiguratorModelRepository $configuratorModelRepository
     *
     * @return array
     */
    private function prepareCarBestsellersData(array $carBestsellers, ConfiguratorModelRepository $configuratorModelRepository)
    {
        $carBestsellersData = [];

        if($carBestsellers) {
            foreach ($carBestsellers as $carBestseller) {
                /**
                 * @var CarBestseller $carBestseller
                 */
                $version = $configuratorModelRepository->getFilteredModels(array('version'),array('vehicle_id' => $carBestseller->getVersion()))->fetch();
                $body = $configuratorModelRepository->getFilteredModels(array('cabine'),array('cabine' => $carBestseller->getBody()))->fetch();

                $carBestsellersData[] = [
                    'id' => $carBestseller->getId(),
                    'mark' => $carBestseller->getMark(),
                    'model' => $carBestseller->getModel(),
                    'version' => $version['version'],
                    'body' => $body['cabine'],
                    'active_from' => $carBestseller->getActiveFrom(),
                    'active_to' => $carBestseller->getActiveTo(),
                    'active' => $carBestseller->getActive(),
                ];
            }
        }

        return $carBestsellersData;
    }
}
