<?php

namespace ProcessMaker\ImportExport\Exporters;

use ProcessMaker\Models\ProcessCategory;

class TemplateExporter extends ExporterBase
{
    public $handleDuplicatesByIncrementing = ['name'];

    public function export() : void
    {
        $this->exportCategories();
    }

    public function import() : bool
    {
        $this->associateCategories(ProcessCategory::class, 'process_category_id');
        if (!$this->model->process_category_id) {
            // set category by defatult
            $this->model->process_category_id = ProcessCategory::firstOrCreate(
                ['name' => 'Default Templates'],
                [
                    'name' => 'Default Templates',
                    'status' => 'ACTIVE',
                    'is_system' => 0,
                ]
            )->getKey();
        }
        $this->model->setProcessCategoryIdAttribute($this->model->process_category_id);
        $this->model->save();

        return true;
    }
}
