<?php

namespace App\Controller\Panel;

use App\Entity\Discount;
use App\Entity\Dealer;
use App\Form\Panel\DiscountType;
use App\Repository\DiscountRepository;
use App\Traits\Panel\JATOMapperTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Datatable\Panel\ConfiguratorDiscountDatatable;
use App\Repository\ConfiguratorModelRepository;
use App\Repository\ConfiguratorMarkRepository;

/**
 * Class PanelConfiguratorDiscountController
 *
 * @package App\Controller\Panel
 */
class PanelConfiguratorDiscountController extends AbstractController
{
    use JATOMapperTrait;

    /**
     * @param Request $request
     * @param TranslatorInterface $translator
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function index(Request $request, TranslatorInterface $translator)
    {
        if (!$this->isGranted('discount_read')) {
            $flashbag = $this->get('session')->getFlashBag();
            $flashbag->add("danger", $translator->trans('form.messages.accessDenied', [], 'discount'));
            return $this->redirectToRoute('panel_dashboard_index');
        }

        return $this->render('panel/configurator/discount/index.html.twig', []);
    }

    /**
     * @param Request $request
     * @param ConfiguratorDiscountDatatable $datatable
     *
     * @return JsonResponse
     */
    public function list(Request $request, ConfiguratorDiscountDatatable $datatable)
    {
        $data = $datatable->getData();
        return new JsonResponse($data, 200);
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
        $level = $request->get('level');
		$mark = $request->get('mark');
		$model = $request->get('model');
		$body = $request->get('body');

		$responseArray = array();
			
		if($level == 'model' && $mark) {
			$vehicles = $modelRespository->getFilteredModels(array('model_slug','model_name'),array('mark' => $mark),array(),'model_slug')->fetchAll();
			foreach($vehicles as $vehicle){
				$responseArray[] = array(
					"vehicle_id" => $vehicle['model_slug'],
					"name" => $vehicle['model_name']
				);
			}
			
		} elseif ($level == 'body' && $model) {
			$cabines = $modelRespository->getFilteredModels(array('cabine'),array('model_slug' => $model),array(),'cabine')->fetchAll();
			foreach($cabines as $cabine){
				$responseArray[] = array(
					"vehicle_id" => $cabine['cabine'],
					"name" => $modelRespository->getSchemaDescription($modelRespository::$JATO_STANDARD_MAPPER['cabine'], $cabine['cabine'])
				);
			}
		} elseif ($level == 'version' && $model) {
		    if(is_array($model) && isset($model[0])) {
                $criteria = [
                    'model_slug' => $model[0],
                ];
            } else {
                $criteria = [
                    'model_slug' => $model,
                ];
            }

            if(true == is_array($body) && count($body) > 0) {
                $vehicles = [];

                foreach ($body as $item) {
                    $criteria['cabine'] = $item;

                    $vehiclesPart = $modelRespository->getFilteredModels(array('version','vehicle_id','jato_vehicle_id'), $criteria)->fetchAll();

                    $vehicles = array_merge($vehicles, $vehiclesPart);
                }
            } else {
                $vehicles = $modelRespository->getFilteredModels(array('version','vehicle_id','jato_vehicle_id'), $criteria)->fetchAll();
            }

			foreach($vehicles as $vehicle){
				$responseArray[] = array(
					"vehicle_id" => $vehicle['jato_vehicle_id'],
					"name" => $vehicle['version']
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
     *
     * @return JsonResponse
     */
	public function exclusionList(Request $request)
    {
		$dealer_id = $request->get('dealer');
		
		$dealer = $this->getDoctrine()
			->getRepository(Dealer::class)
			->find($dealer_id);
				
		$discounts = $this->getDoctrine()
			->getRepository(Discount::class)
			->findBy(['dealer' => $dealer]);		
		
		$responseArray = array();
        foreach($discounts as $discount){
            
			$amountType = ($discount->getAmountType() == 'Q' ? " EUR" : "%");
			$responseArray[] = array(
                "value" => $discount->getId(),
                "name" => $discount->getTypeCategory($discount->getType()).' '.$discount->getValue().$amountType
            );
        }
        
        return new JsonResponse($responseArray);
    }

    /**
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param ConfiguratorModelRepository $modelRepository
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function add(Request $request, TranslatorInterface $translator, ConfiguratorModelRepository $modelRepository)
    {
        $flashbag = $this->get('session')->getFlashBag();

        if (!$this->isGranted('discount_create')) {
            $flashbag->add("danger", $translator->trans('form.messages.accessDenied', [], 'discount'));
            return $this->redirectToRoute('panel_configurator_discount_index');
        }

        $discount = new Discount();
        $user   = $this->getUser();
        $em     = $this->getDoctrine()->getManager();
        $form   = $this->createForm(DiscountType::class, $discount, []);

        $form->handleRequest($request);
        /**
         * @var Discount $entity
         */
        $entity = $form->getData();
		
        //if submitted with no errors
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $entity->setCreatedBy($user);

				
				$data = $request->request->all();
//				dump($data);exit;
				
				$entity->setMark($data['discount']['mark']);

				//set archive to false
				$entity->setArchive(false);

				$vehicle = $data['discount']['mark'];
				if(isset($data['discount']['model'])){
					$vehicle = $data['discount']['model'];
				}
				if(isset($data['discount']['version'])){
					$vehicle = $data['discount']['version'];
				}
				if(isset($data['discount']['body'])){
					$vehicle = $data['discount']['body'];
				}
				if(isset($data['discount']['exclusions']))
				{
					foreach($data['discount']['exclusions'] as $exclusionId)
					{
						$exclusion = $em->getRepository(Discount::class)->find($exclusionId);
						$entity->addExclusion($exclusion);					
					}
				}

                if($entity->getMain() == true){
					/* Clear main discount with level */
					$this->clearMainDiscountWithLevel($entity->getLevel(),$vehicle,$entity->getDealer(),$entity->getGroups());
				}
				
                $em->persist($entity);
                $em->flush();

                /* Save children */
                $this->saveChildren($entity, $data);

                /* Index children to parent */
                $entity = $this->indexChildrenToParent($entity, $modelRepository, $data);

                $em->persist($entity);
                $em->flush();

                $flashbag->add("success", $translator->trans('form.messages.addSuccess', [], 'discount'));
                return $this->redirectToRoute('panel_configurator_discount_index');
            } else {
                $flashbag->add("danger", $translator->trans('form.messages.addFail', [], 'discount'));
            }
        }

        return $this->render('panel/configurator/discount/add.html.twig',
                [
                    'form' => $form->createView(),
        ]);
    }

    /**
     * @param Request $request
     * @param $clone
     * @param TranslatorInterface $translator
     * @param Discount $discount
     * @param ConfiguratorModelRepository $modelRepository
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function edit(Request $request, $clone = 0, TranslatorInterface $translator, Discount $discount, ConfiguratorModelRepository $modelRepository)
    {
        ini_set('max_execution_time', 0);
        $flashbag = $this->get('session')->getFlashBag();

        if($clone == 1) {
            $discount = clone $discount;
        }

        //when page is not found
        if (empty($discount)) {
            $flashbag->add("danger", $translator->trans('form.messages.discountNotFound', [], 'discount'));
            return $this->redirectToRoute('panel_discount_index');
        }

        if (!$this->isGranted('discount_update', $discount)) {
            $flashbag->add("danger", $translator->trans('form.messages.accessDenied', [], 'discount'));
            return $this->redirectToRoute('panel_discount_index');
        }

        $user   = $this->getUser();
        $em     = $this->getDoctrine()->getManager();
        $form   = $this->createForm(DiscountType::class, $discount, []);
        $entity = $form->getData();

        $form->handleRequest($request);

        //if submitted with no errors
        if ($form->isSubmitted()) {
			
			if ($form->isValid()) {
                $entity->setUpdatedBy($user);
				
				//remove exclusions before add
				$this->clearExclusions($entity->getDealer());
				
				$data = $request->request->all();
				if(isset($data['discount']['exclusions']))
				{
					foreach($data['discount']['exclusions'] as $exclusionId)
					{
						$exclusion = $em->getRepository(Discount::class)->find($exclusionId);
						$entity->addExclusion($exclusion);					
					}
				}

				if($entity->getMain() == true){
					$vehicle = $entity->getMark();
					if($entity->getLevel() == 'MODEL'){
						$vehicle = $entity->getModel();
					}elseif($entity->getLevel() == 'BODY'){
						$vehicle = $entity->getBody();
					}elseif($entity->getLevel() == 'VERSION'){
						$vehicle = $entity->getVersion();
					}

					/* Clear main discount with level */
					$this->clearMainDiscountWithLevel($entity->getLevel(),$vehicle,$entity->getDealer(),$entity->getGroups());
					$entity->setMain(true);
				}

                $em->persist($entity);
                $em->flush();

                /* Save children */
                $this->saveChildren($entity, $data, $clone);

                /* Index children to parent */
                $entity = $this->indexChildrenToParent($entity, $modelRepository, $data);

                $em->persist($entity);
                $em->flush();

                $flashbag->add("success", $translator->trans('form.messages.editSuccess', [], 'discount'));
                return $this->redirectToRoute('panel_configurator_discount_index');
            } else {
                $flashbag->add("danger", $translator->trans('form.messages.editFail', [], 'discount'));
            }
        }

        return $this->render('panel/configurator/discount/edit.html.twig',
                [
                    'form' => $form->createView(),
                    'discount' => $discount,
        ]);
    }

    /**
     * Create parent discount for all discounts that have parent=null
     */
    public function createParentDiscount()
    {
        ini_set('max_execution_time', 0);

        $doctrine = $this->getDoctrine();
        $entityManager = $doctrine->getManager();
        $discountRepository = $doctrine->getRepository('App:Discount');
        $discounts = $discountRepository->findWithOutParent();

        if($discounts) {
            foreach ($discounts as $parent) {
                /**
                 * @var Discount $child
                 */
                /**
                 * @var Discount $parent
                 */
                $child = clone $parent;

                $child->setParent($parent);

                $entityManager->persist($parent);
                $entityManager->persist($child);
                $entityManager->flush();
            }

            echo 'done';
        }
    }

    /**
     * @param Discount $parent
     * @param array $postData
     * @param $clone
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function saveChildren(Discount $parent, array $postData, $clone = 0)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $discountRepository = $entityManager->getRepository('App:Discount');

        switch ($parent->getLevel()) {
            case 'MARK':
                $childern[] = $postData['discount']['mark'];
                break;
            case 'MODEL':
                $childern = $postData['discount']['model'];
                break;
            case 'BODY':
                $childern = $postData['discount']['body'];
                break;
            case 'VERSION':
                $childern = $postData['discount']['version'];
                break;
        }

        if($childern) {
            foreach ($childern as $childName) {
                $child = false;

                switch ($parent->getLevel()) {
                    case 'MODEL':
                        $child = $discountRepository->findChildWithModel($parent, $childName);
                        break;
                    case 'BODY':
                        $child = $discountRepository->findChildWithBody($parent, $childName);
                        break;
                    case 'VERSION':
                        $child = $discountRepository->findChildWithVersion($parent, $childName);
                        break;
                }

                if($child) {
                    $this->updateChild($parent, $child);
                } else {
                    $this->addChild($parent, $childName);
                }
            }
        }

        if($clone == 0) {
            $entityChildren = $discountRepository->findByParent($parent);

            if ($entityChildren) {
                foreach ($entityChildren as $entityChild) {
                    $found = false;

                    foreach ($childern as $childName) {
                        if ($parent->getLevel() == 'MODEL' && $childName == $entityChild->getModel()) {
                            $found = true;
                        } elseif ($parent->getLevel() == 'BODY' && $childName == $entityChild->getBody()) {
                            $found = true;
                        } elseif ($parent->getLevel() == 'VERSION' && $childName == $entityChild->getVersion()) {
                            $found = true;
                        }
                    }

                    if ($found == false) {
                        $this->deleteChild($entityChild);
                    }
                }
            }
        }
    }

    /**
     * @param Discount $child
     */
    private function deleteChild(Discount $child)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $child->setArchive(true);

        $entityManager->persist($child);
        $entityManager->flush();
    }

    /**
     * @param Discount $parent
     * @param Discount $child
     */
    private function updateChild(Discount $parent, Discount $child)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $child->setType($parent->getType());
        $child->setAmountType($parent->getAmountType());
        $child->setComment($parent->getComment());
        $child->setDescription($parent->getDescription());
        $child->setDeliveryTime($parent->getDeliveryTime());
        $child->setValue($parent->getValue());
        $child->setActive($parent->getActive());
        $child->setActiveFrom($parent->getActiveFrom());
        $child->setActiveTo($parent->getActiveTo());
        $child->setGroups($parent->getGroups());
        $child->setLevel($parent->getLevel());
        $child->setCarneoProvision($parent->getCarneoProvision());
        $child->setCarneoAmountType($parent->getCarneoAmountType());
        $child->setMain($parent->getMain());
        $child->setObligatory($parent->getObligatory());
        $child->setName($parent->getName());
        $child->setFrontName($parent->getFrontName());
        $child->setDealer($parent->getDealer());
        $child->setUpdatedBy($parent->getUpdatedBy());
        $child->setDiscount($parent->getDiscount());

        $entityManager->persist($child);
        $entityManager->flush();
    }

    /**
     * @param Discount $parent
     * @param string $childName
     */
    private function addChild(Discount $parent, string $childName)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $child = clone $parent;

        $child->setParent($parent);

        switch ($parent->getLevel()) {
            case 'MODEL':
                $child->setModel($childName);
                break;
            case 'BODY':
                $child->setBody($childName);
                break;
            case 'VERSION':
                $child->setVersion($childName);
                break;
        }

        $entityManager->persist($child);
        $entityManager->flush();
    }

    /**
     * @param Discount $parent
     */
    private function deleteChildren(Discount $parent)
    {
        $children = $parent->getDiscounts();

        if($children) {
            foreach ($children as $child) {
                /**
                 * @var Discount $child
                 */
                $this->deleteChild($child);
            }
        }
    }
	
	private function clearExclusions(Dealer $dealer)
	{
		$allExclusions = $this->getDoctrine()
			->getRepository(Discount::class)
			->findBy(['dealer' => $dealer]);
		foreach($allExclusions as $exclusion)
		{
			$exclusion->setDiscount(null);
		}
	}

    /**
     * Handles Discount activation Request
     *
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param DiscountRepository $discountRepository
     * @param Discount|null $discount
     *
     * @return JsonResponse
     */
    public function activate(Request $request, TranslatorInterface $translator, DiscountRepository $discountRepository, Discount $discount = null)
    {
        if (empty($discount)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.activationFailed', [], 'discount').': '.$translator->trans('form.messages.discountNotFound', [], 'discount'),
                ], 200);
        }

        if (!$this->isGranted('discount_update', $discount)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.activationFailed', [], 'discount').': '.$translator->trans('form.messages.noPermission', [], 'discount'),
                ], 200);
        }

        $em = $this->getDoctrine()->getManager();

        $discount->setActive(true);

        $em->persist($discount);
        $em->flush();

        /*
         * Activate children
         */
        $this->changeChildrenStatus($discount, $discountRepository, true);

        return new JsonResponse([
            'success' => true,
            'message' => $translator->trans('form.messages.activationSuccess', [], 'discount'),
            ], 200);
    }

    /**
     * Handles Discount deactivation Request. XHR Only
     *
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param DiscountRepository $discountRepository
     * @param Discount|null $discount
     *
     * @return JsonResponse
     */
    public function deactivate(Request $request, TranslatorInterface $translator, DiscountRepository $discountRepository, Discount $discount = null)
    {
        if (empty($discount)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.deactivationFailed', [], 'discount').': '.$translator->trans('form.messages.discountNotFound', [], 'discount'),
                ], 200);
        }


        if (!$this->isGranted('discount_update', $discount)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.deactivationFailed', [], 'discount').': '.$translator->trans('form.messages.noPermission', [], 'discount'),
                ], 200);
        }

        $em = $this->getDoctrine()->getManager();
        $discount->setActive(false);

        $em->persist($discount);
        $em->flush();

        /*
         * Deactivate children
         */
        $this->changeChildrenStatus($discount, $discountRepository, false);


        return new JsonResponse([
            'success' => true,
            'message' => $translator->trans('form.messages.deactivationSuccess', [], 'discount'),
            ], 200);
    }

    /**
     * Handles Discount deletion Request. XHR Only
     *
     * @param Request $request An instance of Translator
     * @param Request $request An instance of Request
     * @param TranslatorInterface $translator An instance of Translator
     * @param Discount $discount
     *
     * @return JsonResponse Json Resonse is returned
     */
    public function delete(Request $request, TranslatorInterface $translator, Discount $discount = null)
    {
        if (empty($discount)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.deleteFailed', [], 'discount').': '.$translator->trans('form.messages.discountNotFound', [], 'discount'),
                ], 200);
        }

        if (!$this->isGranted('discount_delete', $discount)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.deleteFailed', [], 'discount').': '.$translator->trans('form.messages.noPermission', [], 'discount'),
                ], 200);
        }

        $em = $this->getDoctrine()->getManager();

        $this->deleteChildren($discount);

        $discount->setArchive(true);

        $em->persist($discount);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => $translator->trans('form.messages.deleteSuccess', [], 'discount'),
            ], 200);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function massDelete(Request $request)
    {
        $result = [
            'success' => true,
        ];

        $postData = $request->request->all();
        $ids = $postData['ids'];

        if(count($ids) > 0) {
            foreach ($ids as $id) {
                $parent = $this->getDoctrine()->getRepository('App:Discount')->find($id);

                if($parent) {
                    $this->deleteChildren($parent);
                    $this->deleteChild($parent);
                }
            }

            $result['success'] = true;
        }

        return new JsonResponse($result);
    }

    /**
     * @param Discount $parent
     * @param DiscountRepository $discountRepository
     * @param bool $status
     */
    private function changeChildrenStatus(Discount $parent, DiscountRepository $discountRepository, bool $status = true)
    {
        $children = $discountRepository->findByParent($parent);
        $entityManager = $this->getDoctrine()->getManager();

        if($children) {
            foreach ($children as $child) {
                /**
                 * @var Discount $child
                 */
                $child->setActive($status);

                $entityManager->persist($child);
                $entityManager->flush();
            }
        }
    }

    /**
     * @param string $level
     */
    private function clearMainDiscountWithLevel($level,$vehicle,$dealer,$group)
    {
        $em = $this->getDoctrine()->getManager();
		$discountRepository = $em->getRepository('App:Discount');
        $discounts = $discountRepository->findBy([
			'level' => $level,
			strtolower($level) => $vehicle,
			'dealer' => $dealer
		]);

        foreach ($discounts as $discount) {
			if($discount->getGroups() == $group){
				$discount->setMain(false);

				$em->persist($discount);
				$em->flush();
			}
        }
    }

    /**
     * @param Discount $parent
     * @param ConfiguratorModelRepository $modelRepository
     * @param string|null $level
     *
     * @return string
     */
    private function getChildrenByLevel(Discount $parent, ConfiguratorModelRepository $modelRepository, string $level = null)
    {
        $children = $this->getDoctrine()->getRepository('App:Discount')->findByParent($parent);
        $result = [];

        if(true == is_null($level)) {
            $level = $parent->getLevel();
        }

        if($children) {
            foreach ($children as $child) {
                /**
                 * @var Discount $child
                 */

                switch ($level) {
                    case 'MODEL':
                        $result[] = $child->getModel();
                        break;
                    case 'BODY':
                        $result[] = $modelRepository->getSchemaDescription($modelRepository::$JATO_STANDARD_MAPPER['cabine'], $child->getBody());
                        break;
                    case 'VERSION_BODY':
                        $vehicle = $modelRepository->getJatoVersion($child->getVersion())->fetch();
                        $body = $modelRepository->getSchemaDescription($modelRepository::$JATO_STANDARD_MAPPER['cabine'], $vehicle['id_' . $modelRepository::$JATO_STANDARD_MAPPER['cabine']]);

                        if(false == in_array($body, $result)) {
                            $result[] = $body;
                        }
                        break;
                    case 'VERSION':
                        $vehicle = $modelRepository->getJatoVersion($child->getVersion())->fetch();
                        $result[] = $vehicle[$modelRepository::$JATO_VERSION_MAPPER['version']];
                        break;
                }
            }
        }

        return join(', ', $result);
    }

    /**
     * @param Discount $discount
     * @param ConfiguratorModelRepository $modelRepository
     * @param array $data
     *
     * @return Discount
     */
    private function indexChildrenToParent(Discount $discount, ConfiguratorModelRepository $modelRepository, array $data)
    {
        switch ($discount->getLevel()) {
            case 'MODEL':
                $discount->setModel($this->getChildrenByLevel($discount, $modelRepository));
                break;
            case 'BODY':
                if(isset($data['discount']['model'][0])) {
                    $discount->setModel($data['discount']['model'][0]);
                }
                $discount->setBody($this->getChildrenByLevel($discount, $modelRepository));
                break;
            case 'VERSION':
                if(isset($data['discount']['model'][0])) {
                    $discount->setModel($data['discount']['model'][0]);
                }
                if(isset($data['discount']['body'][0])) {
                    $discount->setBody($data['discount']['body'][0]);
                    $cabine = $this->getChildrenByLevel($discount, $modelRepository, 'VERSION_BODY');
                    $discount->setBody($cabine);
                }
                $discount->setVersion($this->getChildrenByLevel($discount, $modelRepository));
                break;
        }

        return $discount;
    }
}