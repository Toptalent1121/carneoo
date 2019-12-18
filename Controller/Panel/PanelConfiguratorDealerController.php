<?php

namespace App\Controller\Panel;

use App\Entity\Dealer;
use App\Entity\Discount;
use App\Form\Panel\DealerType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Datatable\Panel\DealerDatatable;

class PanelConfiguratorDealerController extends AbstractController
{

    public function index(Request $request, TranslatorInterface $translator)
    {
        if (!$this->isGranted('dealer_read')) {
            $flashbag = $this->get('session')->getFlashBag();
            $flashbag->add("danger", $translator->trans('form.messages.accessDenied', [], 'dealer'));
            return $this->redirectToRoute('panel_dashboard_index');
        }

        return $this->render('panel/configurator/dealer/index.html.twig', []);
    }

    public function list(Request $request, DealerDatatable $datatable)
    {
        $data = $datatable->getData();
        return new JsonResponse($data, 200);
    }

    public function add(Request $request, TranslatorInterface $translator)
    {
        $flashbag = $this->get('session')->getFlashBag();

        if (!$this->isGranted('dealer_create')) {
            $flashbag->add("danger", $translator->trans('form.messages.accessDenied', [], 'dealer'));
            return $this->redirectToRoute('panel_configurator_dealer_index');
        }
		
        $dealer = new Dealer();
		$data = $request->request->all();
        $user   = $this->getUser();
        $em     = $this->getDoctrine()->getManager();
        $form   = $this->createForm(DealerType::class, $dealer, []);

        $form->handleRequest($request);
        $entity = $form->getData();

        //if submitted with no errors
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $entity->setCreatedBy($user);
				$entity->setMail($data['dealer']['mail']);
				$entity->setPerson($data['dealer']['person']);
				
                $em->persist($entity);
                $em->flush();
                $flashbag->add("success", $translator->trans('form.messages.addSuccess', [], 'dealer'));
                return $this->redirectToRoute('panel_configurator_dealer_index');
            } else {
                $flashbag->add("danger", $translator->trans('form.messages.addFail', [], 'dealer'));
            }
        }

        return $this->render('panel/configurator/dealer/add.html.twig',
                [
                    'form' => $form->createView(),
        ]);
    }

    public function edit(Request $request, TranslatorInterface $translator, Dealer $dealer)
    {
        $flashbag = $this->get('session')->getFlashBag();

        //when page is not found
        if (empty($dealer)) {
            $flashbag->add("danger", $translator->trans('form.messages.dealerNotFound', [], 'dealer'));
            return $this->redirectToRoute('panel_dealer_index');
        }

        if (!$this->isGranted('dealer_update', $dealer)) {
            $flashbag->add("danger", $translator->trans('form.messages.accessDenied', [], 'dealer'));
            return $this->redirectToRoute('panel_dealer_index');
        }
		$data = $request->request->all();
		
		$user   = $this->getUser();
        $em     = $this->getDoctrine()->getManager();
        $form   = $this->createForm(DealerType::class, $dealer, []);
        $entity = $form->getData();

        $form->handleRequest($request);

        //if submitted with no errors
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
				$entity->setUpdatedBy($user);
				$entity->setMail($data['dealer']['mail']);
				$entity->setPerson($data['dealer']['person']);
				
                $em->persist($entity);
                $em->flush();
                $flashbag->add("success", $translator->trans('form.messages.editSuccess', [], 'dealer'));
                return $this->redirectToRoute('panel_configurator_dealer_index');
            } else {
                $flashbag->add("danger", $translator->trans('form.messages.editFail', [], 'dealer'));
            }
        }
		$mails = $entity->getMail();
		$persons = $entity->getperson();
        return $this->render('panel/configurator/dealer/edit.html.twig',
                [
                    'form' => $form->createView(),
					'mails' => $mails,
					'persons' => $persons
        ]);
    }

    /**
     * Handles Dealers activation Request
     * @param Request $request An instance of Translator
     * @param TranslatorInterface $translator An instance of Translator
     * @param Dealer $dealer An Instance od Dealer user
     * @return JsonResponse Json Resonse is returned
     */
    public function activate(Request $request, TranslatorInterface $translator, Dealer $dealer = null)
    {
        if (empty($dealer)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.activationFailed', [], 'dealer').': '.$translator->trans('form.messages.dealerNotFound', [], 'dealer'),
                ], 200);
        }

        if (!$this->isGranted('dealer_update', $dealer)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.activationFailed', [], 'dealer').': '.$translator->trans('form.messages.noPermission', [], 'dealer'),
                ], 200);
        }

        $em = $this->getDoctrine()->getManager();
		
		$discounts = $em->getRepository(Discount::class)->findBy(
			['dealer' => $dealer]
		);
		
		//activate all binded discounts
		foreach($discounts as $discount)
		{
			$discount->setActive(true);
			$em->persist($discount);
		}

        $dealer->setActive(true);

        $em->persist($dealer);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => $translator->trans('form.messages.activationSuccess', [], 'dealer'),
            ], 200);
    }

    /**
     * Handles Dealers deactivation Request. XHR Only
     * @param Request $request An instance of request
     * @param TranslatorInterface $translator An instance of Translator
     * @param Dealer $dealer An Instance od Dealer user
     * @return JsonResponse Json Resonse is returned
     */
    public function deactivate(Request $request, TranslatorInterface $translator, Dealer $dealer = null)
    {
        if (empty($dealer)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.deactivationFailed', [], 'dealer').': '.$translator->trans('form.messages.dealerNotFound', [], 'dealer'),
                ], 200);
        }


        if (!$this->isGranted('dealer_update', $dealer)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.deactivationFailed', [], 'dealer').': '.$translator->trans('form.messages.noPermission', [], 'dealer'),
                ], 200);
        }

        $em = $this->getDoctrine()->getManager();
        $dealer->setActive(false);
		
		//deactivate all binded discounts
		$discounts = $em->getRepository(Discount::class)->findBy(
			['dealer' => $dealer]
		);
		
		foreach($discounts as $discount)
		{
			$discount->setActive(false);
			$em->persist($discount);
		}

        $em->persist($dealer);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => $translator->trans('form.messages.deactivationSuccess', [], 'dealer'),
            ], 200);
    }

    /**
     * Handles Dealer deletion Request. XHR Only
     * @param Request $request An instance of Translator
     * @param Request $request An instance of Request
     * @param TranslatorInterface $translator An instance of Translator
     * @param Dealer $dealer An Instance od Dealer
     * @return JsonResponse Json Resonse is returned
     */
    public function delete(Request $request, TranslatorInterface $translator, Dealer $dealer = null)
    {
        if (empty($dealer)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.deleteFailed', [], 'dealer').': '.$translator->trans('form.messages.dealerNotFound', [], 'dealer'),
                ], 200);
        }

        if (!$this->isGranted('dealer_delete', $dealer)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.deleteFailed', [], 'dealer').': '.$translator->trans('form.messages.noPermission', [], 'dealer'),
                ], 200);
        }

        $em = $this->getDoctrine()->getManager();

        $em->remove($dealer);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => $translator->trans('form.messages.deleteSuccess', [], 'dealer'),
            ], 200);
    }
}