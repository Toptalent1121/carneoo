<?php

namespace App\Controller\Panel;

use App\Entity\Role;
use App\Form\Panel\RoleType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class PanelRoleController extends AbstractController
{

    public function index(Request $request, TranslatorInterface $translator)
    {
        if (!$this->isGranted('role_read')) {
            $flashbag = $this->get('session')->getFlashBag();
            $flashbag->add("danger", $translator->trans('form.messages.accessDenied', [], 'role'));
            return $this->redirectToRoute('panel_dashboard_index');
        }

        $roles = $this->getDoctrine()->getManager()->getRepository('App\Entity\Role')->findAll();
        return $this->render('panel/role/index.html.twig',
                [
                    'roles' => $roles,
        ]);
    }

    public function add(Request $request, TranslatorInterface $translator)
    {
        $flashbag = $this->get('session')->getFlashBag();

        if (!$this->isGranted('role_add')) {
            $flashbag->add("danger", $translator->trans('form.messages.accessDenied', [], 'role'));
            return $this->redirectToRoute('panel_role_index');
        }

        $permissions = $this->getParameter('permissions');
        $role        = new Role();
        $user        = $this->getUser();
        $em          = $this->getDoctrine()->getManager();
        $form        = $this->createForm(RoleType::class, $role,
            [
                'permissions' => $permissions,
        ]);
        $data        = $request->request->all();
        $form->handleRequest($request);
        $entity      = $form->getData();

        //if submitted with no errors
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $resultsPermission = $this->getCollectedPermissions($data);
                $entity->setCreatedBy($user)
                    ->setPermissions(json_encode($resultsPermission))
                    ->setPrimaryRole(false);

                $em->persist($entity);
                $em->flush();
                $flashbag->add("success", $translator->trans('form.messages.addSuccess', [], 'role'));
                return $this->redirectToRoute('panel_role_index');
            } else {
                $flashbag->add("danger", $translator->trans('form.messages.addFail', [], 'role'));
            }
        }

        return $this->render('panel/role/add.html.twig',
                [
                    'form' => $form->createView(),
                    'permissions' => $permissions,
        ]);
    }

    public function edit(Request $request, TranslatorInterface $translator, Role $role)
    {
        $flashbag = $this->get('session')->getFlashBag();

        //when page is not found
        if (empty($role)) {
            $flashbag->add("danger", $translator->trans('form.messages.roleNotFound', [], 'role'));
            return $this->redirectToRoute('panel_role_index');
        }


        if (!$this->isGranted('role_update', $role)) {
            $flashbag->add("danger", $translator->trans('form.messages.accessDenied', [], 'role'));
            return $this->redirectToRoute('panel_role_index');
        }

        $permissions = $this->getParameter('permissions');
        $user        = $this->getUser();
        $em          = $this->getDoctrine()->getManager();
        $form        = $this->createForm(RoleType::class, $role, [
            'permissions' => $permissions,
        ]);
        $data        = $request->request->all();
        $entity      = $form->getData();

        $form->handleRequest($request);

        //if submitted with no errors
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $resultsPermission = $this->getCollectedPermissions($data);
                $entity->setUpdatedBy($user)
                    ->setPermissions(json_encode($resultsPermission));

                $em->persist($entity);
                $em->flush();
                $flashbag->add("success", $translator->trans('form.messages.editSuccess', [], 'role'));
                return $this->redirectToRoute('panel_role_index');
            } else {
                $flashbag->add("danger", $translator->trans('form.messages.editFail', [], 'role'));
            }
        }

        return $this->render('panel/role/edit.html.twig',
                [
                    'form' => $form->createView(),
                    'permissions' => $permissions,
                    'requestData' => $data,
        ]);
    }

    /**
     * Handles Roles activation Request
     * @param TranslatorInterface $translator An instance of Translator
     * @param Role $role An Instance od Role user
     * @return JsonResponse Json Resonse is returned
     */
    public function activate(Request $request, TranslatorInterface $translator, Role $role = null)
    {
        if (empty($role)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.activationFailed', [], 'role').': '.$translator->trans('form.messages.roleNotFound', [], 'role'),
                ], 200);
        }

        if (!$this->isGranted('role_update', $role)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.activationFailed', [], 'role').': '.$translator->trans('form.messages.noPermission', [], 'role'),
                ], 200);
        }

        $em = $this->getDoctrine()->getManager();

        $role->setActive(true);

        $em->persist($role);
        $em->flush();

        $view = $this->generateRoleList();

        return new JsonResponse([
            'success' => true,
            'message' => $translator->trans('form.messages.activationSuccess', [], 'role'),
            'view' => $view,
            ], 200);
    }

    /**
     * Handles Roles deactivation Request. XHR Only
     * @param Request $request An instance of request
     * @param TranslatorInterface $translator An instance of Translator
     * @param Role $role An Instance od Role user
     * @return JsonResponse Json Resonse is returned
     */
    public function deactivate(Request $request, TranslatorInterface $translator, Role $role = null)
    {
        if (empty($role)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.deactivationFailed', [], 'role').': '.$translator->trans('form.messages.roleNotFound', [], 'role'),
                ], 200);
        }


        if (!$this->isGranted('role_update', $role)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.deactivationFailed', [], 'role').': '.$translator->trans('form.messages.noPermission', [], 'role'),
                ], 200);
        }

        $em = $this->getDoctrine()->getManager();
        $role->setActive(false);
        $em->persist($role);
        $em->flush();

        $view = $this->generateRoleList();

        return new JsonResponse([
            'success' => true,
            'message' => $translator->trans('form.messages.deactivationSuccess', [], 'role'),
            'view' => $view,
            ], 200);
    }

    /**
     * Handles Role deletion Request. XHR Only
     * @param Request $request An instance of Request
     * @param TranslatorInterface $translator An instance of Translator
     * @param Role $role An Instance od Role
     * @return JsonResponse Json Resonse is returned
     */
    public function delete(Request $request, TranslatorInterface $translator, Role $role = null)
    {
        if (empty($role)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.deleteFailed', [], 'role').': '.$translator->trans('form.messages.roleNotFound', [], 'role'),
                ], 200);
        }

        if (!$this->isGranted('role_delete', $role)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.deleteFailed', [], 'role').': '.$translator->trans('form.messages.noPermission', [], 'role'),
                ], 200);
        }

        if (!$role->getUsers()->isEmpty()) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.deleteFailed', [], 'role').': '.$translator->trans('form.messages.usersExist', [], 'role'),
                ], 200);
        }

        $em = $this->getDoctrine()->getManager();

        $em->remove($role);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'view' => $this->generateRoleList(),
            'message' => $translator->trans('form.messages.deleteSuccess', [], 'role'),
            ], 200);
    }

    /**
     * Collectes all premitions set to the role
     * @param array $data Request data
     * @return type
     */
    private function getCollectedPermissions(array $data)
    {
        $permissions        = $this->getParameter('permissions');
        $resultPermissions  = [];
        $requestPermissions = $data['role']['permissions'];

        foreach ($permissions as $permission => $parmissionValue) {
            if (!array_key_exists($permission, $requestPermissions)) {
                $resultPermissions[$permission] = false;
                continue;
            }

            $resultPermissions[$permission] = $parmissionValue;
        }

        return $requestPermissions;
    }

    private function getAuthorizationRoleName(string $roleName)
    {
        $roleNameChunks = explode(" ", $roleName);
        $authRoleName   = 'ROLE_';
        $authRoleName   .= implode("_", array_map('strtoupper', $roleNameChunks));

        return $authRoleName;
    }

    private function generateRoleList()
    {
        $roles = $this->getDoctrine()->getManager()->getRepository('App\Entity\Role')->findAll();
        $view  = $this->renderView('panel/role/_partials/view.html.twig',
            [
                'roles' => $roles,
        ]);
        return $view;
    }
}