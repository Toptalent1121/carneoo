<?php

namespace App\Datatable\Panel;

use App\Datatable\Panel\DatatableAbstract;

class SliderDatatable extends DatatableAbstract
{
    protected $entityName = 'App\Entity\Banner';

    /**
     * Defines what should be rendered in columns row. Key is the name of the column defined in JS
     * If no column definition has been set - default DB value will be returned when row rendering
     * If name of the template has been set - this tamplate will be rendered as the result
     * if function has been defined - callback will be called
     */
    protected function setColumnsRendererDefinition()
    {
        $columns = [
            'name' => function($entity, $value) {
                return $value;
            },
            'active' => function($entity, $value){
                return $value;
            },
            '_actions' => 'panel/banner/datatable/actions_column.html.twig'
        ];
        return $columns;
    }
}