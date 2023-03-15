<?php

namespace ProcessMaker\ImportExport\Exporters;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ProcessMaker\Assets\ScreensInScreen;
use ProcessMaker\ImportExport\DependentType;
use ProcessMaker\Models\ProcessTemplates;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\Script;

class TemplateExporter extends ExporterBase
{
    public function export() : void
    {
    }

    public function import() : bool
    {
        $request = $this;
        $mode = $request->mode;
        $model = $request->model;

        $updateData = [
            'name' => $model->name,
            'description' => $model->description,
            'process_id' => $model->process_id,
            'user_id' => $model->user_id,
            'manifest' => $model->manifest,
            'svg' => $model->svg,
            'process_template_category_id' => $model->process_template_category_id,
            'is_system' =>$model->is_system,
        ];

        switch ($mode) {
            case 'update':
                ProcessTemplates::where('uuid', $model->uuid)->update($updateData);

                return true;
            case 'new':
            case 'copy':
                ProcessTemplates::create(['uuid' => $model->uuid], $updateData);

                return true;
            default:
                // code...
                break;
        }
    }

    private function validateImportedTemplate($request)
    {
    }
}
