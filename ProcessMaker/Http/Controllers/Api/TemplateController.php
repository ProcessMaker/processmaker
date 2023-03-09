<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Api\ExportController;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\TemplateCollection;
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
     * Get list Process Templates
     *
     * @param string $type
     * @param Request $request
     *
     * @return ApiCollection
     *
     * @OA\Get(
     *     path="/templates/{type}",
     *     summary="Returns all processes templates that the user has access to",
     *     operationId="getProcessTemplates",
     *     tags={"Process Templates"},
     *     @OA\Parameter(ref="#/components/parameters/filter"),
     *     @OA\Parameter(ref="#/components/parameters/order_by"),
     *     @OA\Parameter(ref="#/components/parameters/order_direction"),
     *     @OA\Parameter(ref="#/components/parameters/per_page"),
     *     @OA\Parameter(ref="#/components/parameters/status"),
     *     @OA\Parameter(ref="#/components/parameters/include"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="list of process templates",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Process"),
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 ref="#/components/schemas/metadata",
     *             ),
     *         ),
     *     ),
     * )
     */
    public function index(string $type, Request $request)
    {
        $templates = (new $this->types[$type][1])->index($request);

        return new TemplateCollection($templates);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(string $type, Request $request)
    {
        $existingTemplate = (new $this->types[$type][1])->existingTemplate($request);

        if ($existingTemplate) {
            return response()->json(['message' => ucfirst($type) . ' Template with the same name already exists'], 409);
        } else {
            (new $this->types[$type][1])->save($request);
        }
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
}
