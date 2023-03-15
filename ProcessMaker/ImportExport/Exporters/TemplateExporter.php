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

        $fields = [
            'name',
            'description',
            'process_id',
            'user_id',
            'manifest',
            'svg',
            'process_template_category_id',
            'is_system',
            'created_at',
            'updated_at',
        ];

        $processTemplate = [
            'uuid' => $model->uuid,
            // Fill in all other necessary fields here
        ];

        switch ($mode) {
            case 'update':
                ProcessTemplates::where('uuid', $model->uuid)->update($fields);

                return true;

            case 'new':
            case 'copy':
                ProcessTemplates::updateOrCreate(['uuid' => $model->uuid], $fields);

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
