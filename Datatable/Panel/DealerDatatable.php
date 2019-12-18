<?php

namespace App\Datatable\Panel;

use App\Datatable\Panel\DatatableAbstract;

class DealerDatatable extends DatatableAbstract
{
    protected $entityName = 'App\Entity\Dealer';
    
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
        $columns = [
            '_actions' => 'panel/configurator/dealer/datatable/actions_column.html.twig'
        ];
        return $columns;
    }
}