<?php

namespace ProcessMaker\Contracts;

use Illuminate\Database\Eloquent\Model;

interface ProcessAssetsInterface
{
    /**
     * Get references to export from a PM4 model
     *
     * @param Model $model
     * @param array $references
     *
     * @return array
     */
    public function referencesToExport($model, array $references = []);

    /**
     * Update references when import a PM4 model
     *
     * @param Model $model
     * @param array $references
     * @return void
     */
    public function updatetReferences($model, array $references = []);
}
