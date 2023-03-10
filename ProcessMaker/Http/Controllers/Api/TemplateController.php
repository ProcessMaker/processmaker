<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\TemplateCollection;
use ProcessMaker\Models\Process;
use ProcessMaker\Templates\ProcessTemplate;

class TemplateController extends Controller
{
    protected array $types = [
        'process' => [Process::class, ProcessTemplate::class],
    ];

    /**
     * Get list Process Templates
     *
     * @param string $type
     * @param \Illuminate\Http\Request $request
     * @return TemplateCollection
     */
    public function index(string $type, Request $request)
    {
        $templates = (new $this->types[$type][1])->index($request);

        return new TemplateCollection($templates);
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
        $existingTemplateId = (new $this->types[$type][1])->existingTemplate($request);

        if ($existingTemplateId) {
            return response()->json([
                'name' => ['The template name must be unique.'],
                'id' => $existingTemplateId,
            ], 409);
        }

        (new $this->types[$type][1])->save($request);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  string  $type
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function update(string $type, Request $request)
    {
        if (!isset($request->process_id)) {
            // This is an update from the template configs page. We need to check if the template name was updated and already exists
            $existingTemplate = (new $this->types[$type][1])->existingTemplate($request);

            if ($existingTemplate) {
                return response()->json(['message' => ucfirst($type) . ' Template with the same name already exists'], 409);
            }
        }
        // This is an update from the process designer page. This will overwrite the template with new data. We do not need to check for existing templates
        (new $this->types[$type][1])->update($request);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \ProcessMaker\Models\Template  $template
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $type, Request $request)
    {
        //
    }
}
