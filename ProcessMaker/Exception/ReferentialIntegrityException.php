<?php

namespace ProcessMaker\Exception;

use Exception;
use Illuminate\Database\Eloquent\Model;

class ReferentialIntegrityException extends Exception
{

    public function __construct(Model $source, Model $parent) {
        $source = class_basename($source) . ":" . $source->getKey();
        $parent = class_basename($parent) . ":" . $parent->getKey();
        parent::__construct(__('Integrity exception when deleting :source record, because referenced by :parent', compact('source', 'parent')),
            422 );
    }
}
