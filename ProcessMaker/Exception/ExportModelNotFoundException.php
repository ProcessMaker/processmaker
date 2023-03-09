<?php

namespace ProcessMaker\Exception;

use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ExportModelNotFoundException extends Exception
{
    public function __construct(ModelNotFoundException $e, $exporter)
    {
        $notFoundClass = class_basename($e->getModel());
        $id = implode(',', $e->getIds());
        $type = $exporter->getClassName();
        $name = $exporter->getName($exporter->model);
        parent::__construct("the $notFoundClass with id $id could not be found while exporting the $type '$name'.");
    }
}
