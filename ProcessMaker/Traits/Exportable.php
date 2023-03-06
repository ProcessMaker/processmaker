<?php

namespace ProcessMaker\Traits;

trait Exportable
{
    use HasUuids;

    private $_preventSavingDiscardedModel = false;

    public static function bootPreventDiscardedModelsFromSaving()
    {
        static::saving(function ($model) {
            if ($this->_preventSavingDiscardedModel) {
                return false;
            }
        });
    }

    public function preventSavingDiscardedModel()
    {
        $this->_preventSavingDiscardedModel = true;
    }
}
