<?php

namespace App\Controller\Panel;

use App\Entity\Page;
use App\Entity\Widget;
use App\Form\Panel\WidgetType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityNotFoundException;

class PanelWidgetController extends AbstractController
{

    public function __construct(AuthorizationCheckerInterface $authChecker)
    {
        $this->authChecker = $authChecker;
    }

    public function index(Request $request)
    {
        //if no permission granted for users read - redirect to dashboard
        if (!$this->isGranted('widget_read')) {
            $flashbag = $this->get('session')->getFlashBag();
            $flashbag->add("danger", $translator->trans('form.messages.accessDenied', [], 'widget'));
            return $this->redirectToRoute('panel_dashboard_index');
        }

        $page       = $request->get('page');
        $widgetsAll = [];
        $widgets    = [];
        $entity     = null;
        $em         = $this->getDoctrine()->getManager();
        $pageRepo   = $em->getRepository('App\Entity\Page');
        $widgetRepo = $em->getRepository('App\Entity\Widget');

        //get all widgets if no slug provided
        if (empty($page)) {
            $widgetsAll = $widgetRepo->findAll();
        } else {
            $entity = $pageRepo->findOneBy([
                'slug' => $page
            ]);
        }

        //get widgets
        if (!empty($entity)) {
            $widgets = $entity->getWidgets();
        }

        return $this->render('panel/widget/index.html.twig',
                [
                    'slug' => $page,
                    'entity' => $entity,
                    'widgets' => $widgets,
                    'widgetsAll' => $widgetsAll,
        ]);
    }

    public function add(Request $request, TranslatorInterface $translator, Page $page)
    {
        $flashbag = $this->get('session')->getFlashBag();

        if (!$this->isGranted('widget_create')) {
            $flashbag->add("danger", $translator->trans('form.messages.accessDenied', [], 'widget'));
            return $this->redirectToRoute('panel_widget_index');
        }

        $widget = new Widget();
        $user   = $this->getUser();
        $em     = $this->getDoctrine()->getManager();
        $form   = $this->createForm(WidgetType::class, $widget);
        $form->handleRequest($request);
        $entity = $form->getData();

        //if submitted with no errors
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $entity->setPage($page);
                $repo  = $em->getRepository('App\Entity\Widget');
                $order = $repo->getMaxOrder() + 1;

                $entity->setCreatedBy($user)
                    ->setWidgetOrder($order);


                $em->persist($entity);
                $em->flush();
                $flashbag->add("success", $translator->trans('form.messages.addSuccess', [], 'widget'));
                return $this->redirectToRoute('panel_page_edit', [
                    'id' => $page->getId(),
                ]);
            } else {
                $flashbag->add("danger", $translator->trans('form.messages.addFail', [], 'widget'));
            }
        }

        return $this->render('panel/widget/add.html.twig',
                [
                    'form' => $form->createView(),
                    'page' => $page,
        ]);
    }

    public function edit(Request $request, TranslatorInterface $translator, Widget $widget = null)
    {
        $flashbag = $this->get('session')->getFlashBag();

        //when widget is not found
        if (empty($widget)) {
            $flashbag->add("danger", $translator->trans('form.messages.widgetNotFound', [], 'widget'));
            return $this->redirectToRoute('panel_widget_index');
        }

        if (!$this->isGranted('widget_update', $widget)) {
            $flashbag->add("danger", $translator->trans('form.messages.accessDenied', [], 'widget'));
            return $this->redirectToRoute('panel_widget_index');
        }

        $form   = $this->createForm(WidgetType::class, $widget);
        $em     = $this->getDoctrine()->getManager();
        $entity = $form->getData();

        $form->handleRequest($request);

        //if submitted with no errors
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $user = $this->getUser();
                $entity->setUpdatedBy($user);

                $em->persist($entity);
                $em->flush();

                $flashbag->add("success", $translator->trans('form.messages.editSuccess', [], 'widget'));
                return $this->redirectToRoute('panel_page_edit', [
                    'id' => $widget->getPage()->getId(),
                ]);
            } else {
                $flashbag->add("danger", $translator->trans('form.messages.editFail', [], 'widget'));
            }
        }

        return $this->render('panel/widget/edit.html.twig',
                [
                    'form' => $form->createView(),
                    'widget' => $widget,
        ]);
    }

    /**
     * Handles Widgets activation Request: XHR only
     * @param Request $request An instance of Request
     * @param TranslatorInterface $translator An instance of Translator
     * @param Widget $widget An Instance od Widget user
     * @return JsonResponse Json Resonse is returned
     */
    public function activate(Request $request, TranslatorInterface $translator, Widget $widget = null)
    {
        $listType = $request->request->get('listType');
        if (empty($widget)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.activationFailed', [], 'widget').': '.$translator->trans('form.messages.widgetNotFound', [], 'widget'),
                ], 200);
        }

        //Redactor is allowd to activate only his own widgets
        if (!$this->isGranted('widget_update', $widget)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.activationFailed', [], 'widget').': '.$translator->trans('form.messages.noPermission', [], 'widget'),
                ], 200);
        }

        $em = $this->getDoctrine()->getManager();
        $widget->setActive(true);

        $em->persist($widget);
        $em->flush();

        $view = $this->generateOrderView($request, $translator, $listType);

        return new JsonResponse([
            'success' => true,
            'message' => $translator->trans('form.messages.activationSuccess', [], 'widget'),
            'view' => $view,
            ], 200);
    }

    /**
     * Handles Widgets deactivation Request
     * @param Request $request An instance of Request
     * @param TranslatorInterface $translator An instance of Translator
     * @param Widget $widget An Instance od Widget user
     * @return JsonResponse Json Resonse is returned
     */
    public function deactivate(Request $request, TranslatorInterface $translator, Widget $widget = null)
    {
        $listType = $request->request->get('listType');
        if (empty($widget)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.deactivationFailed', [], 'widget').': '.$translator->trans('form.messages.widgetNotFound', [], 'widget'),
                ], 200);
        }

        //Redactor is allowd to deactivate only his widgets
        if (!$this->isGranted('widget_update', $widget)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.deactivationFailed', [], 'widget').': '.$translator->trans('form.messages.noPermission', [], 'widget'),
                ], 200);
        }

        $em = $this->getDoctrine()->getManager();
        $widget->setActive(false);

        $em->persist($widget);
        $em->flush();

        $view = $this->generateOrderView($request, $translator, $listType);

        return new JsonResponse([
            'success' => true,
            'message' => $translator->trans('form.messages.deactivationSuccess', [], 'widget'),
            'view' => $view,
            ], 200);
    }

    /**
     * Handles Widget deletion Request
     * @param Request $request An instance of Request
     * @param TranslatorInterface $translator An instance of Translator
     * @param Widget $widget An Instance od Widget
     * @return JsonResponse Json Resonse is returned
     */
    public function delete(Request $request, TranslatorInterface $translator, Widget $widget = null)
    {
        if (empty($widget)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.deleteFailed', [], 'widget').': '.$translator->trans('form.messages.widgetNotFound', [], 'widget'),
                ], 200);
        }

        if (!$this->isGranted('widget_delete', $widget)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.deleteFailed', [], 'widget').': '.$translator->trans('form.messages.noPermission', [], 'widget'),
                ], 200);
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($widget);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => $translator->trans('form.messages.deleteSuccess', [], 'widget'),
            ], 200);
    }

    /**
     * Handles Widget order change Request. XHR only
     * @param Request $request An instance of Request
     * @param TranslatorInterface $translator An instance of Translator
     * @return JsonResponse Json Resonse is returned
     */
    public function order(Request $request, TranslatorInterface $translator)
    {
        //original view for rollback purpose
        $view = $this->generateOrderView($request, $translator);

        try {
            $this->handleOrderChange($request, $translator);
            $view = $this->generateOrderView($request, $translator); //view with new order
        } catch (EntityNotFoundException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.orderFailed', [], 'widget').': '.$e->getMessage(),
                'view' => $view,
                ], 200);
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'OK',
            'view' => $view,
            ], 200);
    }

    /**
     * Saves checned Widget Order into database
     * @param Request $request Request
     * @param TranslatorInterface $translator Translator
     * @throws EntityNotFoundException An exception is thrown in case of errors
     */
    private function handleOrderChange(Request $request,
                                       TranslatorInterface $translator)
    {
        $em           = $this->getDoctrine()->getManager();
        $widgetRepo   = $em->getRepository('App\Entity\Widget');
        $widgetsOrder = $request->request->get('widgets');
        $a            = 1;

        foreach ($widgetsOrder as $details) {
            $widget = $widgetRepo->findOneBy([
                'id' => $details
            ]);

            if (empty($widget)) {
                throw new EntityNotFoundException($translator->trans('form.messages.widgetNotFound', [], 'widget').' ID: '.$details);
            }

            $widget->setWidgetOrder($a);
            $em->persist($widget);
            $a++;
        }
        $em->flush();
        $em->detach($widget);
    }

    /**
     * Generates Widget List based on Request Data
     * @param Request $request Request
     * @param TranslatorInterface $translator Translator
     * @param string $ulAttrId Id of the UL element for widget list. Available options: 'widget-list-sortable', 'widget-list-not-sortable'. By default 'widget-list-sortable'
     * @return string HTML is returned
     */
    private function generateOrderView(Request $request, TranslatorInterface $translator, $ulAttrId = 'widget-list-sortable')
    {
        $pageId     = $request->request->get('pageId');
        $pageSlug   = $request->request->get('slug');
        $em         = $this->getDoctrine()->getManager();
        $pageRepo   = $em->getRepository('App\Entity\Page');
        $widgetRepo = $em->getRepository('App\Entity\Widget');
        $widgets    = [];
        $widgetsAll = [];

        $pageEntity = $pageRepo->findOneBy([
            'id' => $pageId,
            'slug' => $pageSlug,
        ]);

        if (empty($pageSlug)) {
            $widgetsAll = $widgetRepo->findAll();
        }

        //this method preventing getting cached widgets
        if (!empty($pageEntity)) {
            $widgets = $widgetRepo->findBy([
                'page' => $pageEntity
                ], [
                'widget_order' => 'ASC'
            ]);
        }

        $view = $this->renderView('panel/widget/_partials/view.html.twig',
            [
                'widgets' => count($widgets) > 0 ? $widgets : [],
                'attrID' => 'widget-list-sortable',
                'entity' => empty($pageEntity) ? null : $pageEntity,
                'widgetsAll' => $widgetsAll,
        ]);
        return $view;
    }
}