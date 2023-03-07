<?php

namespace ProcessMaker\Templates;

use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;

class ProcessTemplate implements TemplateInterface
{
    public function save($request) : bool
    {
        // Save Process Templates
        dd('PROCESS TEMPLATES SAVE', $request);
        //dd('STORE');
    //     $processId = $request->id;
    //     $name = $request->name;
    //     $description = $request->description;
    //     $category = $request->template_category_id;

    //     $svg = Process::select('svg')->where('id', $processId)->firstOrFail();
    //     $response = (new ExportController)->manifest($type, $processId);
    //     $dependents = $response->getData('dependents');
    //     $manifest = $response->getData();
        //   // dd('he');
    //     Template::create([
    //         'name' => $name,
    //         'description' => $description,
    //         'manifest' => $manifest,
    //        'svg' => $svg,
    //     ]);
    //     dd('HERE');

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
}
