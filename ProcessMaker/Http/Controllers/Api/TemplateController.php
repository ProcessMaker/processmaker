<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\TemplateCollection;
use ProcessMaker\Models\Template;

class TemplateController extends Controller
{
    protected array $types = [
        'process' => [Process::class, ProcessTemplate::class, ProcessCategory::class, 'process_category_id', 'process_templates'],
    ];

    private $template;

    public function __construct(Template $template)
    {
        $this->template = $template;
    }

    /**
     * Get list Process Templates
     *
     * @param string $type
     * @param \Illuminate\Http\Request $request
     * @return TemplateCollection
     */
    public function index(string $type, Request $request)
    {
        $templates = $this->template->index($type, $request);

        return new TemplateCollection($templates);
    }

    public function show(string $type, Request $request)
    {
        return $this->template->show($type, $request);
    }

    /**
     * Store a newly created template
     *
     * @param string $type
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(string $type, Request $request)
    {
        $existingTemplate = $this->template->checkForExistingTemplates($type, $request);

        if (!is_null($existingTemplate)) {
            return response()->json([
                'name' => ['The template name must be unique.'],
                'id' => $existingTemplate['id'],
                'templateName' => $existingTemplate['name'],
            ], 409);
        }
        $request->validate(Template::rules($request->id, $this->types[$type][4]));

        return $this->template->store($type, $request);
    }

     /**
      * Update the template manifest
      *
      * @param  string  $type
      * @param  Request $request
      * @return \Illuminate\Http\Response
      */
     public function updateTemplateManifest(string $type, int $processId, Request $request)
     {
         return $this->template->updateTemplateManifest($type, $processId, $request);
     }

    /**
     * Update stored template with new.
     *
     * @param  string  $type
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function updateTemplate(string $type, Request $request)
    {
        $request->validate(Template::rules($request->id, $this->types[$type][4]));

        return $this->template->updateTemplate($type, $request);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  string  $type
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function updateTemplateConfigs(string $type, Request $request)
    {
        $request->validate(Template::rules($request->id, $this->types[$type][4]));

        return $this->template->updateTemplateConfigs($type, $request);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  string  $type
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function create(string $type, Request $request)
    {
        $request->validate(Template::rules($request->id, $this->types[$type][4]));

        return $this->template->create($type, $request);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \ProcessMaker\Models\Template  $template
     * @return \Illuminate\Http\Response
     */
    public function delete(string $type, Request $request)
    {
        return $this->template->deleteTemplate($type, $request);
    }
}
