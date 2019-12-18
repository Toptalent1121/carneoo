<?php

namespace App\Datatable\Panel;

use App\Datatable\Panel\DatatableAbstract;
use App\Entity\Discount;
use App\Repository\ConfiguratorModelRepository;
use App\Repository\ConfiguratorMarkRepository;
use Twig_Environment;
use Symfony\Component\Translation\TranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ConfiguratorDiscountDatatable extends DatatableAbstract
{
    protected $entityName = 'App\Entity\Discount';
    
    public function __construct(RequestStack $request, TranslatorInterface $translator, Twig_Environment $templating, EntityManagerInterface $entityManager, ConfiguratorModelRepository $configuratorModelRespository,ConfiguratorMarkRepository $configuratorMarkRespository)
    {
        $this->request                   = $request;
        $this->templating                = $templating;
        $this->translator                = $translator;
        $this->em                        = $entityManager;
        $this->columnsRendererDefinition = $this->setColumnsRendererDefinition();
		$this->configuratorModelRespository = $configuratorModelRespository;
		$this->configuratorMarkRespository = $configuratorMarkRespository;
    }
	
    protected function setColumnsRendererDefinition()
    {
        $columns = [
			'type' => function($entity, $value) {
                return $entity->getTypeCategory();
            },
			'value' => function($entity, $value) {
                if($entity->getAmountType() == 'Q')
					return $value.' EUR';
				else
					return $value.'%';
            },
			'dealer' => function($entity, $value) {
                return $value->getName();
            },
			'level' => function($entity, $value) {
                return $entity->getLevelCategory();
            },
            'model' => function($entity, $value) {
                return $entity->getModel();
            },
			'version' => function($entity, $value) {
                if($value != null){
                    return $value;
                } else {
                    return '-';
                }
            },
			'body' => function($entity, $value) {
                return $entity->getBody();
            },
            '_actions' => 'panel/configurator/discount/datatable/actions_column.html.twig'
        ];
        return $columns;
    }
}