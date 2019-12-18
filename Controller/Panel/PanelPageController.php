<?php

namespace App\Controller\Panel;

use App\Entity\Page;
use App\Form\Panel\PageType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Datatable\Panel\PageDatatable;
use App\Security\Panel\PageVoter;
use Doctrine\ORM\EntityNotFoundException;

class PanelPageController extends AbstractController
{

    public function __construct(AuthorizationCheckerInterface $authChecker)
    {
        $this->authChecker = $authChecker;
    }

    public function index(Request $request, TranslatorInterface $translator)
    {
        //if no permission granted for users read - redirect to dashboard
        if (!$this->isGranted('page_read')) {
            $flashbag = $this->get('session')->getFlashBag();
            $flashbag->add("danger", $translator->trans('form.messages.accessDenied', [], 'page'));
            return $this->redirectToRoute('panel_dashboard_index');
        }

        return $this->render('panel/page/index.html.twig', [
        ]);
    }

    public function list(Request $request, TranslatorInterface $translator)
    {
        $em   = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('App\Entity\Page');
        $data = $repo->getTreeStructure();
        return new JsonResponse($data, 200);
    }

    public function add(Request $request, TranslatorInterface $translator)
    {
        $flashbag = $this->get('session')->getFlashBag();

        if (!$this->isGranted('page_create')) {
            $flashbag->add("danger", $translator->trans('form.messages.accessDenied', [], 'page'));
            return $this->redirectToRoute('panel_page_index');
        }

        $page   = new Page();
        $user   = $this->getUser();
        $em     = $this->getDoctrine()->getManager();
        $form   = $this->createForm(PageType::class, $page);
        $form->handleRequest($request);
        $entity = $form->getData();

        //if submitted with no errors
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $repo  = $em->getRepository('App\Entity\Page');
                $order = $repo->getMaxOrder() + 1;

                $entity
                    ->setCreatedBy($user)
                    ->setPageOrder($order);

                $em->persist($entity);
                $em->flush();

                $flashbag->add("success", $translator->trans('form.messages.addSuccess', [], 'page'));
                return $this->redirectToRoute('panel_page_index');
            } else {
                $flashbag->add("danger", $translator->trans('form.messages.addFail', [], 'page'));
            }
        }

        return $this->render('panel/page/add.html.twig',
                [
                    'form' => $form->createView(),
        ]);
    }

    public function edit(Request $request, TranslatorInterface $translator, Page $page = null)
    {
        $flashbag = $this->get('session')->getFlashBag();

        //when page is not found
        if (empty($page)) {
            $flashbag->add("danger", $translator->trans('form.messages.pageNotFound', [], 'page'));
            return $this->redirectToRoute('panel_page_index');
        }

        if (!$this->isGranted('page_update', $page)) {
            $flashbag->add("danger", $translator->trans('form.messages.accessDenied', [], 'page'));
            return $this->redirectToRoute('panel_page_index');
        }

        $form   = $this->createForm(PageType::class, $page);
        $em     = $this->getDoctrine()->getManager();
        $entity = $form->getData();
        $user   = $this->getUser();
        $widgets = $this->getDoctrine()->getRepository('App:Widget')->getPageWidgets($page);
        
        $form->handleRequest($request);

        //if submitted with no errors
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                /**
                 * @var Page $entity
                 */

                //preventing circular parenthood
                if ($entity->getParent() !== null && $entity->getParent()->getParent() !== null && $entity->getParent()->getParent()->getId() === $entity->getId()) {
                    $flashbag->add("danger", $translator->trans('form.messages.circularParenthoodError', [], 'page'));
                    return $this->render('panel/page/edit.html.twig',
                            [
                                'form' => $form->createView(),
                    ]);
                }

                $entity->setUpdatedBy($user);

                $em->persist($entity);
                $em->flush();

                $flashbag->add("success", $translator->trans('form.messages.editSuccess', [], 'page'));
                return $this->redirectToRoute('panel_page_index');
            } else {
                $flashbag->add("danger", $translator->trans('form.messages.editFail', [], 'page'));
            }
        }

        return $this->render('panel/page/edit.html.twig',
                [
                    'form' => $form->createView(),
                    'page' => $page,
                    'widgets' => $widgets,
        ]);
    }

    /**
     * Handles Pages activation Request. XHR only
     * @param TranslatorInterface $translator An instance of Translator
     * @param Page $page An Instance od Page user
     * @return JsonResponse Json Resonse is returned
     */
    public function activate(TranslatorInterface $translator, Page $page = null)
    {
        if (empty($page)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.activationFailed', [], 'page').': '.$translator->trans('form.messages.pageNotFound', [], 'page'),
                ], 200);
        }

        if (!$this->isGranted('page_update', $page)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.activationFailed', [], 'page').': '.$translator->trans('form.messages.noPermission', [], 'page'),
                ], 200);
        }

        $em = $this->getDoctrine()->getManager();
        $page->setActive(true);
        $em->persist($page);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => $translator->trans('form.messages.activationSuccess', [], 'page'),
            ], 200);
    }

    /**
     * Handles Pages deactivation Request. XHR only
     * @param TranslatorInterface $translator An instance of Translator
     * @param Page $page An Instance od Page user
     * @return JsonResponse Json Resonse is returned
     */
    public function deactivate(TranslatorInterface $translator, Page $page = null)
    {
        if (empty($page)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.deactivationFailed', [], 'page').': '.$translator->trans('form.messages.pageNotFound', [], 'page'),
                ], 200);
        }

        //Redactor is allowd to deactivate only his pages
        if (!$this->isGranted('page_update', $page)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.deactivationFailed', [], 'page').': '.$translator->trans('form.messages.noPermission', [], 'page'),
                ], 200);
        }

        $em = $this->getDoctrine()->getManager();
        $page->setActive(false);
        $em->persist($page);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => $translator->trans('form.messages.deactivationSuccess', [], 'page'),
            ], 200);
    }

    /**
     * Handles Page deletion Request. XHR only
     * @param Request $request An instance of Request
     * @param TranslatorInterface $translator An instance of Translator
     * @param Page $page An Instance od Page
     * @return JsonResponse Json Resonse is returned
     */
    public function delete(Request $request, TranslatorInterface $translator, Page $page = null)
    {
        if (empty($page)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.deleteFailed', [], 'page').': '.$translator->trans('form.messages.pageNotFound', [], 'page'),
                ], 200);
        }

        if (!$this->isGranted('page_delete', $page)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.deleteFailed', [], 'page').': '.$translator->trans('form.messages.noPermission', [], 'page'),
                ], 200);
        }

        if (!$page->getWidgets()->isEmpty()) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.deleteFailed', [], 'page').': '.$translator->trans('form.messages.widgetsExists', [], 'page'),
                ], 200);
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($page);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => $translator->trans('form.messages.deleteSuccess', [], 'page'),
            ], 200);
    }

    /**
     * Handles Page order change Request. XHR only
     * @param Request $request An Instance of request
     * @param TranslatorInterface $translator An instance of Translator
     * @return JsonResponse Json Resonse is returned
     */
    public function order(Request $request, TranslatorInterface $translator)
    {
        $data = $request->request->all();
        $tree = $data['tree'];

        if (empty($tree)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.orderFailed', [], 'page').': '.$translator->trans('form.messages.orderIncompleteData', [], 'page'),
                ], 200);
        }

        try {
            $this->updateTreeStructure($tree);
            return new JsonResponse([
                'success' => true,
                'message' => 'OK',
                ], 200);
        } catch (EntityNotFoundException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.orderFailed', [], 'page').': '.$translator->trans('form.messages.orderNoEntityFound', [], 'page'),
                ], 200);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.orderFailed', [], 'page').': '.$e->getMessage(),
                ], 200);
        }
    }

    /**
     * Updates page tree structure with new order values
     * @param Array $collection Collection of pages
     */
    private function updateTreeStructure($collection)
    {
        $a  = 1;
        $em = $this->getDoctrine()->getManager();
        foreach ($collection as $node) {
            $pageId = $node['id'];
            $entity = $this->getEntity($pageId);

            //setting up the parent relation
            if (!empty($node['parent'])) {
                $parent = $this->getEntity($node['parent']);
            } else {
                $parent = null;
            }

            $entity->setPageOrder($a);
            $entity->setParent($parent);

            $em->persist($entity);

            if (array_key_exists('children', $node)) {
                $this->updateTreeStructure($node['children']);
            }
            $a++;
        }
        $em->flush();
    }

    /**
     * Returnes Entity if exists
     * @param integer $id Id of the Page entity
     * @return Page an instance of entity is returned
     * @throws EntityNotFoundException
     */
    private function getEntity($id)
    {
        $em     = $this->getDoctrine()->getManager();
        $repo   = $em->getRepository('App\Entity\Page');
        $entity = $repo->findOneBy([
            'id' => $id
        ]);

        if (empty($entity)) {
            throw new EntityNotFoundException('Unable to find Page with given ID: '.$id);
        }

        return $entity;
    }
}