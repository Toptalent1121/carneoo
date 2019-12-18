<?php

namespace App\Controller\Panel;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Admin;
use App\Form\Panel\AdminType;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Datatable\Panel\AdminDatatable;

class PanelAdminController extends AbstractController
{
    protected $encoder;
    protected $authChecker;

    public function __construct(UserPasswordEncoderInterface $encoder, AuthorizationCheckerInterface $authChecker)
    {
        $this->encoder     = $encoder;
        $this->authChecker = $authChecker;
    }

    public function index(Request $request, TranslatorInterface $translator)
    {

        //if no permission granted for users read - redirect to dashboard
        if (!$this->isGranted('user_read')) {
            $flashbag = $this->get('session')->getFlashBag();
            $flashbag->add("danger", $translator->trans('form.messages.accessDenied', [], 'admin'));
            return $this->redirectToRoute('panel_dashboard_index');
        }


        return $this->render('panel/admin/index.html.twig',
                [
                    'controller_name' => 'PanelAdminController',
        ]);
    }

    public function list(Request $request, AdminDatatable $datatable, TranslatorInterface $translator)
    {
        $data = $datatable->getData();
        return new JsonResponse($data, 200);
    }

    public function add(Request $request, TranslatorInterface $translator)
    {
        $flashbag = $this->get('session')->getFlashBag();

        if (!$this->isGranted('user_create')) {
            $flashbag->add("danger",
                $translator->trans('form.messages.accessDenied', [], 'admin'));
            return $this->redirectToRoute('panel_admin_index');
        }

        $admin = new Admin();
        $data  = $request->request->all();
        $user  = $this->getUser();
        $em    = $this->getDoctrine()->getManager();
        $roles = $em->getRepository('App\Entity\Role')->getFilteredRoles();
        $form  = $this->createForm(AdminType::class, $admin, ['user' => $user, 'roles' => $roles]);
        $form->handleRequest($request);

        //if submitted with no errors
        if ($form->isSubmitted()) {
            $entity = $form->getData();
            if ($form->isValid()) {
                $role = $em->getRepository('app\Entity\Role')->findOneBy([
                    'id' => $data['admin']['role'][0]
                ]);

                $admin->setPassword($this->encoder->encodePassword($admin, $entity->getPassword()))
                    ->setRole($role)
                    ->setRoles(['ROLE_ADMIN']); //for login purpose only

                $em->persist($entity);
                $em->flush();
                $flashbag->add("success",
                    $translator->trans('form.messages.addSuccess', [], 'admin'));
                return $this->redirectToRoute('panel_admin_index');
            } else {
                $flashbag->add("danger",
                    $translator->trans('form.messages.addFail', [], 'admin'));
            }
        }

        return $this->render('panel/admin/add.html.twig',
                [
                    'form' => $form->createView(),
        ]);
    }

    /**
     * Handles Users edition Request
     * @param Request $request An instance of Request
     * @param TranslatorInterface $translator An instance of Translator
     * @param Admin $admin An Instance od Admin user
     * @return Either Edit Form is rendered or redirection to another route is made
     */
    public function edit(Request $request, TranslatorInterface $translator, Admin $admin = null)
    {
        $flashbag    = $this->get('session')->getFlashBag();
        $user        = $this->getUser();
        $oldPassword = $admin->getPassword();

        //when user is not found i.e. manipulation with URL Address
        if (empty($admin)) {
            $flashbag->add("danger", $translator->trans('form.messages.userNotFound', [], 'admin'));
            return $this->redirectToRoute('panel_admin_index');
        }

        if (!$this->isGranted('user_update', $admin)) {
            $flashbag->add("danger", $translator->trans('form.messages.noPermission', [], 'admin'));
            return $this->redirectToRoute('panel_admin_index');
        }

        $em    = $this->getDoctrine()->getManager();
        $data  = $request->request->all();
        $roles = $em->getRepository('App\Entity\Role')->getFilteredRoles();
        $form  = $this->createForm(AdminType::class, $admin, ['user' => $user, 'roles' => $roles]);
        $form->handleRequest($request);

        //form is submitted and no errors
        if ($form->isSubmitted()) {
            $entity = $form->getData();

            if ($form->isValid()) {
                if ($entity->getPassword() !== null) {
                    $entity->setPassword($this->encoder->encodePassword($entity, $entity->getPassword()));
                } else {
                    //fix for setting password as NULL value by the Form builder
                    $entity->setPassword($oldPassword);
                }
                $role = $em->getRepository('app\Entity\Role')->findOneBy([
                    'id' => $data['admin']['role'][0]
                ]);

                $entity->setRole($role);

                $em->persist($entity);
                $em->flush();
                $flashbag->add("success", $translator->trans('form.messages.editSuccess', [], 'admin'));
                return $this->redirectToRoute('panel_admin_index');
            } else {
                $flashbag->add("danger", $translator->trans('form.messages.editFail', [], 'admin'));
            }
        }

        return $this->render('panel/admin/edit.html.twig',
                [
                    'user' => $admin,
                    'form' => $form->createView(),
        ]);
    }

    /**
     * Handles Users activation Request. XHR Only
     * @param TranslatorInterface $translator An instance of Translator
     * @param Admin $admin An Instance od Admin user
     * @return JsonResponse Json Resonse is returned
     */
    public function activate(TranslatorInterface $translator, Admin $admin = null)
    {
        if (empty($admin)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.activationFailed', [], 'admin').': '.$translator->trans('form.messages.userNotFound', [], 'admin'),
                ], 200);
        }

        if (!$this->isGranted('user_update', $admin)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.activationFailed', [], 'admin').': '.$translator->trans('form.messages.noPermission', [], 'admin'),
                ], 200);
        }

        $em = $this->getDoctrine()->getManager();
        $admin->setActive(true);
        $em->persist($admin);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => $translator->trans('form.messages.activationSuccess', [], 'admin'),
            ], 200);
    }

    /**
     * Handles Users eactivation Request. XHR Only
     * @param TranslatorInterface $translator An instance of Translator
     * @param Admin $admin An Instance od Admin user
     * @return JsonResponse Json Resonse is returned
     */
    public function deactivate(TranslatorInterface $translator, Admin $admin = null)
    {
        if (empty($admin)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.deactivationFailed', [], 'admin').': '.$translator->trans('form.messages.userNotFound', [], 'admin'),
                ], 200);
        }

        if (!$this->isGranted('user_update', $admin)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.activationFailed', [], 'admin').': '.$translator->trans('form.messages.noPermission', [], 'admin'),
                ], 200);
        }

        $em = $this->getDoctrine()->getManager();
        $admin->setActive(false);
        $em->persist($admin);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => $translator->trans('form.messages.deactivationSuccess', [], 'admin'),
            ], 200);
    }

    /**
     * Handles Users deletion Request
     * @param TranslatorInterface $translator An instance of Translator
     * @param Admin $admin An Instance od Admin user
     * @return JsonResponse Json Resonse is returned
     */
    public function delete(Request $request, TranslatorInterface $translator, Admin $admin = null)
    {
        if (empty($admin)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.deleteFailed', [], 'admin').': '.$translator->trans('form.messages.userNotFound', [], 'admin'),
                ], 200);
        }

        if (!$this->isGranted('user_delete', $admin)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.delateFailed', [], 'admin').': '.$translator->trans('form.messages.noPermission', [], 'admin'),
                ], 200);
        }

        if ($this->getUser()->getId() === $admin->getId()) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.delateFailed', [], 'admin').': '.$translator->trans('form.messages.noPermission', [], 'admin'),
                ], 200);
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($admin);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => $translator->trans('form.messages.deleteSuccess', [], 'admin'),
            ], 200);
    }
}