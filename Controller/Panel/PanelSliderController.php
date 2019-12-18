<?php

namespace App\Controller\Panel;

use App\Datatable\Panel\SliderDatatable;
use App\Entity\Slider;
use App\Form\SliderType;
use App\Repository\SliderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class PanelSliderController
 *
 * @package App\Controller\Panel
 */
class PanelSliderController extends AbstractController
{
    /**
     * @param SliderRepository $sliderRepository
     *
     * @return Response
     */
    public function index(SliderRepository $sliderRepository): Response
    {
        return $this->render('slider/index.html.twig', [
            'sliders' => $sliderRepository->findAll(),
        ]);
    }

    /**
     * @param Request $request
     * @param SliderDatatable $datatable
     * @param TranslatorInterface $translator
     *
     * @return JsonResponse
     */
    public function list(Request $request, SliderDatatable $datatable, TranslatorInterface $translator)
    {
        $data = $datatable->getData();
        return new JsonResponse($data, 200);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function add(Request $request): Response
    {
        $slider = new Slider();
        $form = $this->createForm(SliderType::class, $slider);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($slider);
            $entityManager->flush();

            return $this->redirectToRoute('slider_index');
        }

        return $this->render('slider/new.html.twig', [
            'slider' => $slider,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param Slider $slider
     *
     * @return Response
     */
    public function show(Slider $slider): Response
    {
        return $this->render('slider/show.html.twig', [
            'slider' => $slider,
        ]);
    }

    /**
     * @param Request $request
     * @param Slider $slider
     *
     * @return Response
     */
    public function edit(Request $request, Slider $slider): Response
    {
        $form = $this->createForm(SliderType::class, $slider);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('slider_index', [
                'id' => $slider->getId(),
            ]);
        }

        return $this->render('slider/edit.html.twig', [
            'slider' => $slider,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param Request $request
     * @param Slider $slider
     * @return Response
     */
    public function delete(Request $request, Slider $slider): Response
    {
        if ($this->isCsrfTokenValid('delete'.$slider->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($slider);
            $entityManager->flush();
        }

        return $this->redirectToRoute('slider_index');
    }
}
