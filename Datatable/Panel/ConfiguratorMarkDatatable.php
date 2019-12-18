<?php

namespace App\Datatable\Panel;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Traits\Panel\DatatableTrait;
use App\Repository\ConfiguratorMarkRepository;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig_Environment;

class ConfiguratorMarkDatatable
{

    use DatatableTrait;
    protected $em;
    protected $request;
	protected $translator;
	protected $router;
    protected $container;	

    public function __construct(RequestStack $request, ConfiguratorMarkRepository $configuratorMarkRespository, TranslatorInterface $translator, UrlGeneratorInterface $router, ContainerInterface $container,Twig_Environment $templating)
    {
        $this->em      = $configuratorMarkRespository;
        $this->request = $request;
		$this->translator = $translator;
		$this->router = $router;
		$this->container = $container;
		$this->templating = $templating;
    }

    public function getData()
    {
        $this->parseRequest();
		$this->decorateOrder();
        $rows = [];

		$results = $this->em->getMarks($this->start, $this->length, $this->orders, $this->search);

        $response = [
            "draw" => $this->draw,
            "recordsTotal" => (int)$this->em->getAllMarks()->rowCount(),
            "recordsFiltered" => (int)$results['countRecords'],
        ];

        if (empty($results['records'])) {
            $response['data'] = $rows;
            return $response;
        }

        foreach ($results['records'] as $record) {
            $rows[] = $this->renderRow($record);
        }

        $response["data"] = $rows;
        return $response;
    }

    protected function renderRow($recordData)
    {
        
		$response = [];
        foreach ($this->columns as $key => $column) {
            $columnName = $column['name'];

            if($columnName == '_actions') {
                $response[] = $this->templating->render('panel/configurator/mark/datatable/actions_column.html.twig',
				[
					'name' => $recordData['name'],
					'active' => $recordData['active']
				]); 
                continue;
            }elseif($columnName == 'logo') {
				if($recordData[$columnName])
					$response[] = '<img style="height:30px;" src="'.$this->container->getParameter('mark_logo_url').$recordData[$columnName].'" />';
				else
					$response[] = '';
			}else {
				$response[] = $recordData[$columnName];
			}
		}
        return $response;
    }
}