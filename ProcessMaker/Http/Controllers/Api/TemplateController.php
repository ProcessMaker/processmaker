<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\TemplateCollection;
use ProcessMaker\Models\Template;

class TemplateController extends Controller
{
    /**
     * Get list Process Templates
     *
     * @param string $type
     * @param \Illuminate\Http\Request $request
     * @return TemplateCollection
     */
    public function index(string $type, Request $request)
    {
        $template = new Template();
        $templates = $template->index($type, $request);

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
        $template = new Template();
        $response = $template->store($type, $request);

        return $response;
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
        $template = new Template();
        $response = $template->updateTemplate($type, $request);

        return $response;
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
