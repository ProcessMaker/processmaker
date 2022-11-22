<?php

namespace ProcessMaker\ImportExport\Entities;

class ModelEntity extends Entity
{
    private $model;

    public function model($id)
    {
        if (!$this->model) {
            $this->model = $this->params['class']::findOrFail($id);
        }

        return $this->model;
    }
}
