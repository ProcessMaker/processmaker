<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Api\ExportController;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\ImportExport\Exporter;
use ProcessMaker\ImportExport\Exporters\ProcessExporter;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Template;
use ProcessMaker\Templates\ProcessTemplate;
use ProcessMaker\Templates\TemplateBase;

class TemplateController extends Controller
{
    protected array $types = [
        'process' => [Process::class, ProcessTemplate::class],
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
        (new $this->types[$type][1])->save($request);

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

    // private function getModel(string $type): Model
    // {
    //     if (isset($this->types[$type])) {
    //         $modelClass = current($this->types[$type]);

    //         return new $modelClass;
    //     }
    //     throw new Exception("Type {$type} not found", 404);
    // }
}
