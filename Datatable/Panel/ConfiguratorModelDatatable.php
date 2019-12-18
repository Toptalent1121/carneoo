<?php

namespace App\Datatable\Panel;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Traits\Panel\DatatableTrait;
use App\Repository\ConfiguratorModelRepository;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ConfiguratorModelDatatable
{

    use DatatableTrait;
    protected $em;
    protected $request;

    public function __construct(RequestStack $request, ConfiguratorModelRepository $configuratorModelRespository, TranslatorInterface $translator, UrlGeneratorInterface $router)
    {
        $this->em         = $configuratorModelRespository;
        $this->request    = $request;
        $this->translator = $translator;
        $this->router     = $router;
    }

    public function getData()
    {
        $this->parseRequest();
        $this->decorateOrder();
        $rows = [];

        $results = $this->em->getModels($this->start, $this->length, $this->orders, $this->search, $this->columns);

        $response = [
            "draw" => $this->draw,
            "recordsTotal" => (int) $this->em->getAllModels()->rowCount(),
            "recordsFiltered" => (int) $results['countRecords'],
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

            if(trim($columnName) === "") {
                $response[] = "";
                continue;
            }
            
            if ($columnName == '_actions') {
                $response[] = '<div class="column-actions-container" data-entity-id="'.$recordData['vehicle_id'].'"><a href="'.$this->router->generate('panel_configurator_model_view', array('id' => $recordData['vehicle_id'])).'" title="'.$this->translator->trans('list.table.details', [], 'model').'" ><i class="iconsmind-Eye-Scan"></i></a></div>';
                continue;
            } elseif ($columnName == 'price') {
                $response[] = number_format((float) $recordData['price'], 2, '.', '');
            } else {
                $response[] = $recordData[$columnName];
            }
        }
        return $response;
    }
}