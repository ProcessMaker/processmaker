<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\ImportExport\Exporter;
use ProcessMaker\ImportExport\Exporters\ProcessExporter;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Template;
use ProcessMaker\Templates\ProcessTemplates;
//use ProcessMaker\Http\Controllers\Api\ExportController;
use ProcessMaker\Templates\TemplateBase;

class TemplateController extends Controller
{
    protected array $types = [
        'process' => [Process::class, ProcessExporter::class],
    ];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(string $type, Request $request)
    {
        $processId = $request->id;
        $name = $request->name;
        $description = $request->description;
        $category = $request->template_category_id;
        $model = $this->getModel($type)->findOrFail($processId);
        $options = $request->options;
        $mode = $request->mode;
        dd($request->options);
        $exporter = new Exporter();
        $exporter->export($model, $this->types[$type][1], $options);
        // $response = (new ExportController)->manifest($type, $id);
        // $manifest = $response->getData();

        // Export the request
    }

    /**
     * Display the specified resource.
     *
     * @param  \ProcessMaker\Models\Template  $template
     * @return \Illuminate\Http\Response
     */
    public function show(Template $template)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \ProcessMaker\Models\Template  $template
     * @return \Illuminate\Http\Response
     */
    public function edit(Template $template)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \ProcessMaker\Models\Template  $template
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Template $template)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \ProcessMaker\Models\Template  $template
     * @return \Illuminate\Http\Response
     */
    public function destroy(Template $template)
    {
        //
    }

    private function getModel(string $type): Model
    {
        if (isset($this->types[$type])) {
            $modelClass = current($this->types[$type]);

            return new $modelClass;
        }
        throw new Exception("Type {$type} not found", 404);
    }
}
