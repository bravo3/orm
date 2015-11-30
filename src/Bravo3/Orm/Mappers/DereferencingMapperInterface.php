<?php
namespace Bravo3\Orm\Mappers;

interface DereferencingMapperInterface
{
    /**
     * Get an entities full class name from its table name
     *
     * @param string $table_name
     * @return string
     */
    public function getClassFromTable($table_name);
}
