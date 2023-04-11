<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\TemplateCollection;
use ProcessMaker\Models\Template;

class TemplateController extends Controller
{
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

    /**
     * Store a newly created template
     *
     * @param string $type
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(string $type, Request $request)
    {
        $this->validateRequest($request);
        $response = $this->template->store($type, $request);

        return $response;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  string  $type
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function updateTemplate(string $type, Request $request)
    {
        $this->validateRequest($request);
        $response = $this->template->updateTemplate($type, $request);

        return $response;
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
        $this->validateRequest($request);
        $response = $this->template->updateTemplateConfigs($type, $request);

        return $response;
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
        $this->validateRequest($request);
        $response = $this->template->create($type, $request);

        return $response;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \ProcessMaker\Models\Template  $template
     * @return \Illuminate\Http\Response
     */
    public function delete(string $type, Request $request)
    {
        $response = $this->template->deleteTemplate($type, $request);

        return $response;
    }

    /**
     * Validate the request input.
     *
     * @param Request $request
     * @return void
     */
    private function validateRequest(Request $request): void
    {
        $this->validate($request, [
            'name' => 'required|string|unique:processes,name|regex:/^[a-zA-Z0-9\s]+$/|max:255',
            'description' => 'required|string',
        ]);
    }
}
