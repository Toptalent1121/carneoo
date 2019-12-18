<?php

namespace App\Datatable\Panel;

use App\Traits\Panel\DatatableTrait;
use Twig_Environment;
use Symfony\Component\Translation\TranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class DatatableAbstract
{

    use DatatableTrait;
    protected $entityName = '';
    protected $columnsRendererDefinition;
    protected $templating;
    protected $translator;
    protected $em;
    protected $request;

    public function __construct(RequestStack $request, TranslatorInterface $translator, Twig_Environment $templating, EntityManagerInterface $entityManager)
    {
        $this->request                   = $request;
        $this->templating                = $templating;
        $this->translator                = $translator;
        $this->em                        = $entityManager;
        $this->columnsRendererDefinition = $this->setColumnsRendererDefinition();
    }

    public function getData()
    {
        $this->parseRequest();
        $this->decorateOrder();
        $repo    = $this->em->getRepository($this->entityName);
        $results = $repo->getRequiredDTData($this->start, $this->length, $this->orders, $this->search, $this->columns, null);
        $objects              = $results["results"];
        $totalObjectsCount    = $repo->getCount();
        $selectedObjectsCount = count($objects);
        $filteredObjectsCount = $results["countResult"];

        $response = [
            "draw" => $this->draw,
            "recordsTotal" => $totalObjectsCount,
            "recordsFiltered" => $filteredObjectsCount,
        ];

        $rows = [];
        foreach ($objects as $entity) {
            $rows[] = $this->renderRow($entity);
        }

        $response["data"] = $rows;
        return $response;
    }

    /**
     * Renders row for DT response
     * @param Admin $entity An instance of entity
     * @return string JSON string is returned
     */
    protected function renderRow($entity)
    {
        $response = [];
        foreach ($this->columns as $key => $column) {
            $response[] = $this->renderCell($entity, $column);
        }
        return $response;
    }

    protected function renderCell($entity, $column)
    {
        $columnName = $column['name'];
        $methodName = 'get'.ucfirst($this->underscoreToCamelcase($columnName));

        $value = null;
        if (method_exists($entity, $methodName)) {
            $value = $entity->$methodName();
        }

        if (!array_key_exists($columnName, $this->columnsRendererDefinition)) {
            return $value;
        }
        
        if (is_callable($this->columnsRendererDefinition[$columnName])) {
            return $this->columnsRendererDefinition[$columnName]($entity, $value);
        }

        if (is_string($this->columnsRendererDefinition[$columnName]) && strpos($this->columnsRendererDefinition[$columnName],
                'twig') !== false) {

            return $this->templating->render($this->columnsRendererDefinition[$columnName],
                    [
                        'entity' => $entity,
                        'value' => $value
            ]);
        }
        return "-";
    }
}