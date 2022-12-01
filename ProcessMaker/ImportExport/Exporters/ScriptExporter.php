<?php

namespace ProcessMaker\ImportExport\Exporters;

use ProcessMaker\Models\ScriptCategory;

class ScriptExporter extends ExporterBase
{
    public function export() : void
    {
        $this->exportCategories();

        $this->addDependent('user', $this->model->runAsUser, UserExporter::class);
    }

    public function import() : bool
    {
        $this->associateCategories(ScriptCategory::class, 'script_category_id');

        $scriptUser = $this->getDependents('user')[0];
        $this->model->run_as_user_id = $scriptUser->model->id;

        return $this->model->save();
    }
}
