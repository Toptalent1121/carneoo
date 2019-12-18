<?php

namespace App\Controller\Panel;

use App\Datatable\Panel\ConfiguratorMarkDatatable;
use App\Repository\ConfiguratorMarkRepository;
use App\Controller\Panel\PanelAbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class PanelConfiguratorMarkController extends PanelAbstractController
{
    public function __construct(AuthorizationCheckerInterface $authChecker,ConfiguratorMarkRepository $configuratorMarkRespository)
    {
        $this->authChecker = $authChecker;
		$this->configuratorMarkRespository = $configuratorMarkRespository;
    }
	
	public function index(Request $request)
    {
        return $this->render('panel/configurator/mark/index.html.twig', array('submenu' => true));
    }

    public function list(Request $request, ConfiguratorMarkDatatable $datatable, TranslatorInterface $translator)
    {
		$data = $datatable->getData();
        return new JsonResponse($data, 200);
    }
	
	public function edit(Request $request, TranslatorInterface $translator)
    {
        $flashbag = $this->get('session')->getFlashBag();
		$mark_id = $request->get('id');
		$mark = $this->configuratorMarkRespository->getMark($mark_id)->fetch();
		
        $form = $this->createFormBuilder()
			->add('logo', FileType::class,
			[
                    'attr' => [
                        'class' => 'col-md-5'
                    ],
					"data_class" => null,
					"label" => $translator->trans('form.elements.file', [], 'mark'),
                    "required" => true
            ])
			->add('url', TextType::class,
			[
                    'attr' => [
                        'class' => 'col-md-5'
                    ],
					"label" => 'URL',
					"data" => $mark['url'],
                    "required" => false
            ])
			->add('submit', SubmitType::class,
                [
                    'attr' => [
                        'class' => 'btn btn-sm btn-success default ml-3'
                    ],
                    'label' => 'form.elements.submit',
            ])
			->getForm();

        //if submitted with no errors
        if ($request->isMethod('POST')) {
			
			$form->handleRequest($request);
			$data = $form->getData();
		   
			if($data['logo']){
				
				$file = $data['logo'];
				$data['logo'] = $this->generateUniqueFileName().'.'.$data['logo']->guessExtension();
				try {
					$file->move(
						$this->getParameter('mark_logo_directory'),
						$data['logo']
					);
				} catch (FileException $e) {
					$flashbag->add("danger", $e->GetMessage());
				}
			}else{
				$data['logo'] = $mark['logo'];
			}			
		    
			$this->configuratorMarkRespository->updateMark($mark['mark'],$data);
					 
            $flashbag->add("success",$translator->trans('form.messages.editSuccess', [], 'mark'));
            return $this->redirectToRoute('panel_configurator_mark_index');        
        }

        return $this->render('panel/configurator/mark/edit.html.twig',
                [
                    'form' => $form->createView(),
					'mark' => $mark['mark']
        ]);
    }
	
	/**
     * Handles Mark activation Request
     * @param Request $request An instance of Translator
     * @param TranslatorInterface $translator An instance of Translator
     * @return JsonResponse Json Resonse is returned
     */
    public function activate($id, TranslatorInterface $translator)
    {
        $this->configuratorMarkRespository->changeStatus($id,1);

        return new JsonResponse([
            'success' => true,
            'message' => $translator->trans('form.messages.activationSuccess', [], 'mark'),
            ], 200);
    }

    /**
     * Handles Mark deactivation Request. XHR Only
     * @param Request $request An instance of request
     * @param TranslatorInterface $translator An instance of Translator
     * @return JsonResponse Json Resonse is returned
     */
    public function deactivate($id, TranslatorInterface $translator)
    {
        $this->configuratorMarkRespository->changeStatus($id,0);

        return new JsonResponse([
            'success' => true,
            'message' => $translator->trans('form.messages.deactivationSuccess', [], 'mark'),
            ], 200);
    }
	
	
}