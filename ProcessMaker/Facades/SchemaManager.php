<?php
namespace ProcessMaker\Facades;

use Illuminate\Support\Facades\Facade;

class SchemaManager extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'schema.manager';
    }
}
