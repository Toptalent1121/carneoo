<?php

namespace App\Controller\Panel;

use App\Entity\Banner;
use App\Form\Panel\BannerType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Datatable\Panel\BannerDatatable;
use App\Service\Panel\FileUploader;
use App\HttpFoundation\File\UploadedBase64EncodedFile;
use App\HttpFoundation\File\Base64EncodedFile;

class PanelBannerController extends AbstractController
{

    public function __construct(AuthorizationCheckerInterface $authChecker)
    {
        $this->authChecker = $authChecker;
    }

    public function index(Request $request, TranslatorInterface $translator)
    {
        //if no permission granted for users read - redirect to dashboard
        if (!$this->isGranted('banner_read')) {
            $flashbag = $this->get('session')->getFlashBag();
            $flashbag->add("danger", $translator->trans('form.messages.accessDenied', [], 'banner'));
            return $this->redirectToRoute('panel_dashboard_index');
        }
        return $this->render('panel/banner/index.html.twig', []);
    }

    /**
     * Handles XHR request for banners table data
     * @param Request $request An instance of Request
     * @param BannerDatatable $datatable Banner Datatable service
     * @param TranslatorInterface $translator Translator instance
     * @return JsonResponse JSON response is returned with collection of records for jquery datatables
     */
    public function list(Request $request, BannerDatatable $datatable, TranslatorInterface $translator)
    {
        $data = $datatable->getData();
        return new JsonResponse($data, 200);
    }

    /**
     * Handles New Banner Createn process
     * @param Request $request An instance of Request
     * @param TranslatorInterface $translator Translator instance
     * @param FileUploader $fileUploader File Uploader service
     * @return Redirect
     */
    public function add(Request $request, TranslatorInterface $translator, FileUploader $fileUploader)
    {
        $flashbag = $this->get('session')->getFlashBag();

        if (!$this->isGranted('banner_create')) {
            $flashbag->add("danger", $translator->trans('form.messages.accessDenied', [], 'banner'));
            return $this->redirectToRoute('panel_banner_index');
        }

        $uploadPath  = $this->getParameter('banner_directory');
        $banner      = new Banner();
        $data        = $request->request->all();
        $user        = $this->getUser();
        $em          = $this->getDoctrine()->getManager();
        $widgetRepo  = $em->getrepository('App\Entity\Widget');
        $form        = $this->createForm(BannerType::class, $banner);
        $entity      = $form->getData();

        $form->handleRequest($request);

        //if submitted with no errors
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $fileContent = $data['banner']['base64'];
                $file        = new UploadedBase64EncodedFile(new Base64EncodedFile($fileContent));

                $fileUploader->setTargetDirectory($uploadPath);

                //uncomment this if you want to upload uncropped file
                $fileName = $fileUploader->upload($request->files->get('banner')['filename']);

                //$fileName = $fileUploader->upload($file);
                $entity->setCreatedBy($user);
                $entity->setFilename($fileName);

                $em->persist($entity);
                $em->flush();
                $flashbag->add("success", $translator->trans('form.messages.addSuccess', [], 'banner'));
                return $this->redirectToRoute('panel_banner_index');
            } else {
                $flashbag->add("danger", $translator->trans('form.messages.addFail', [], 'banner'));
            }
        }

        return $this->render('panel/banner/add.html.twig',
                [
                    'form' => $form->createView(),
                    'cropSettings' => [
                        'width' => $this->getParameter('banner_crop_width'),
                        'height' => $this->getParameter('banner_crop_height'),
                    ],
        ]);
    }

    /**
     * Handles Banner Edition Request
     * @param Request $request An instance of Request
     * @param TranslatorInterface $translator An instance of Translator
     * @param FileUploader $fileUploader FileUploader service
     * @param Banner $banner An instace of edited Banner
     * @return redirect
     */
    public function edit(Request $request, TranslatorInterface $translator, FileUploader $fileUploader, Banner $banner = null)
    {
        $flashbag = $this->get('session')->getFlashBag();

        //when banner is not found
        if (empty($banner)) {
            $flashbag->add("danger",
                $translator->trans('form.messages.bannerNotFound', [], 'banner'));
            return $this->redirectToRoute('panel_banner_index');
        }

        if (!$this->isGranted('banner_update', $banner)) {
            $flashbag->add("danger", $translator->trans('form.messages.accessDenied', [], 'banner'));
            return $this->redirectToRoute('panel_banner_index');
        }

        $data       = $request->request->all();
        $uploadPath = $this->getParameter('banner_directory');
        $form       = $this->createForm(BannerType::class, $banner);

        $em     = $this->getDoctrine()->getManager();
        $entity = $form->getData();
        $form->handleRequest($request);

        //if submitted with no errors
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $user = $this->getUser();

                if (array_key_exists('base64', $data['banner']) && $request->files->get('banner')['filename'] != null) {
                    $fileContent = $data['banner']['base64'];
                    $oldFile     = $banner->getFilename();
                    $file        = new UploadedBase64EncodedFile(new Base64EncodedFile($fileContent));
                    $fileUploader->setTargetDirectory($uploadPath);
                    $fileName    = $fileUploader->upload($request->files->get('banner')['filename']);
                    $entity->setFilename($fileName);

                    //removing old file
                    if (file_exists($uploadPath.'/'.$oldFile)) {
                        unlink($uploadPath.'/'.$oldFile);
                    }
                }

                $entity->setUpdatedBy($user);
                $em->persist($entity);
                $em->flush();

                $flashbag->add("success",
                    $translator->trans('form.messages.editSuccess', [], 'banner'));
                return $this->redirectToRoute('panel_banner_index');
            } else {
                $flashbag->add("danger",
                    $translator->trans('form.messages.editFail', [], 'banner'));
            }
        }

        return $this->render('panel/banner/edit.html.twig',
                [
                    'form' => $form->createView(),
                    'cropSettings' => [
                        'width' => $this->getParameter('banner_crop_width'),
                        'height' => $this->getParameter('banner_crop_height'),
                    ],
        ]);
    }

    /**
     * Handles Banners activation Request. XHR only
     * @param TranslatorInterface $translator An instance of Translator
     * @param Banner $banner An Instance od Banner user
     * @return JsonResponse Json Resonse is returned
     */
    public function activate(TranslatorInterface $translator, Banner $banner = null)
    {
        if (empty($banner)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.activationFailed', [], 'banner').': '.$translator->trans('form.messages.userNotFound', [], 'banner'),
                ], 200);
        }

        if (!$this->isGranted('banner_update', $banner)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.activationFailed', [], 'banner').': '.$translator->trans('form.messages.noPermission', [], 'banner'),
                ], 200);
        }

        $em = $this->getDoctrine()->getManager();
        $banner->setActive(true);
        $em->persist($banner);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => $translator->trans('form.messages.activationSuccess',
                [], 'banner'),
            ], 200);
    }

    /**
     * Handles Banners eactivation Request. XHR Only
     * @param TranslatorInterface $translator An instance of Translator
     * @param Banner $banner An Instance od Banner
     * @return JsonResponse Json Resonse is returned
     */
    public function deactivate(TranslatorInterface $translator, Banner $banner = null)
    {
        if (empty($banner)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.deactivationFailed', [], 'banner').': '.$translator->trans('form.messages.bannerNotFound', [], 'banner'),
                ], 200);
        }

        if (!$this->isGranted('banner_update', $banner)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.deactivationFailed', [], 'banner').': '.$translator->trans('form.messages.noPermission', [], 'banner'),
                ], 200);
        }

        $em = $this->getDoctrine()->getManager();
        $banner->setActive(false);
        $em->persist($banner);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => $translator->trans('form.messages.deactivationSuccess', [], 'banner'),
            ], 200);
    }

    /**
     * Handles Banner deletion Request
     * @param TranslatorInterface $translator An instance of Translator
     * @param Banner $banner An Instance od Banner
     * @return JsonResponse Json Resonse is returned
     */
    public function delete(Request $request, TranslatorInterface $translator, Banner $banner = null)
    {
        $flashbag   = $this->get('session')->getFlashBag();
        $uploadPath = $this->getParameter('banner_directory');

        if (empty($banner)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.deleteFailed', [], 'banner').': '.$translator->trans('form.messages.bannerNotFound', [], 'banner'),
                ], 200);
        }

        if (!$this->isGranted('banner_delete', $banner)) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('form.messages.deleteFailed', [], 'banner').': '.$translator->trans('form.messages.noPermission', [], 'banner'),
                ], 200);
        }

        $em      = $this->getDoctrine()->getManager();
        $oldFile = $banner->getFilename();
        $em->remove($banner);
        $em->flush();

        //removing old file
        if (file_exists($uploadPath.'/'.$oldFile)) {
            unlink($uploadPath.'/'.$oldFile);
        }

        return new JsonResponse([
            'success' => true,
            'message' => $translator->trans('form.messages.deleteSuccess', [], 'banner'),
            ], 200);
    }
}