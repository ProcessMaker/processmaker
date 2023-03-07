<?php

namespace ProcessMaker\Templates;

use ProcessMaker\Http\Controllers\Api\ExportController;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\Template;

class ProcessTemplate implements TemplateInterface
{
    public function save($request) : JsonResponse
    {
        $processId = $request->id;
        $name = $request->name;
        $description = $request->description;
        $category = $request->process_template_category_id;
        $mode = $request->mode;

        // Get process manifest
        $manifest = $this->getManifest('process', $processId);
        $rootUuid = $manifest->getData()->root;
        $export = $manifest->getData()->export;
        $svg = $export->$rootUuid->attributes->svg;

        // Discard ALL assets/dependents
        if ($mode === 'discard') {
            // Get dependents
            $dependents = $export->$root->dependents;
        }

        $template = Template::firstOrCreate([
            'name' => $name,
            'description' => $description,
            'manifest' => $manifest,
            'svg' => $svg,
            'process_id' => $processId,
            'process_template_category_id' => null,
        ]);
        // TODO:: Error when running tests. (vendor/bin/phpunit tests/Feature/Templates/Api/TemplateTest.php) template is not found on 'test' database
        if ($template) {
            return response(['id' => $template->id], 200);
        }

        return response(500);

        // $model = $this->getModel($type)->findOrFail($processId);
        // //$options = $request->options;
        // $mode = $request->mode;

        // //$options = new Options([$screen->uuid => ['mode' => 'discard']]);
        // $options = new Options($request->options);
        // // dd($request->options);
        // $exporter = new Exporter();
        // // dd('HERE');
        // dd($options);
        // $exporter->export($model, $this->types[$type][1], $options);
        // dd('here');
        // $response = (new ExportController)->manifest($type, $id);
        // $manifest = $response->getData();

        // Export the request
    }

    public function view() : bool
    {
        dd('PROCESS TEMPLATE VIEW');
    }

    public function edit() : bool
    {
        dd('PROCESS TEMPLATE EDIT');
    }

    public function destroy() : bool
    {
        dd('PROCESS TEMPLATE DESTROY');
    }

    public function getManifest(string $type, int $id) : object
    {
        $response = (new ExportController)->manifest($type, $id);
        // $manifest = $response->getData();

        return $response;
    }
}
