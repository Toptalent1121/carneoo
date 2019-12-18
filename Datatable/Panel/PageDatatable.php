<?php

namespace App\Datatable\Panel;

use App\Datatable\Panel\DatatableAbstract;

class PageDatatable extends DatatableAbstract
{
    protected $entityName = 'App\Entity\Page';

    /**
     * Defines what should be rendered in columns row. Key is the name of the column defined in JS
     * If no column definition has been set - default DB value will be returned when row rendering
     * If name of the template has been set - this tamplate will be rendered as the result
     * if function has been defined - callback will be called
     * @param type $data
     * @param type $record
     */
    protected function setColumnsRendererDefinition()
    {
        $columns     = [
            'page_order' => 'panel/page/datatable/page_order.html.twig',
            'parent' => 'panel/page/datatable/parent_column.html.twig',
            'updatedAt' => function($entity, $value) {
                return $value->format('Y-m-d H:i:s');
            },
            'created_by' => 'panel/page/datatable/created_by_column.html.twig',
            '_actions' => 'panel/page/datatable/actions_column.html.twig'
        ];
        return $columns;
    }
}