<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use ProcessMaker\Http\Controllers\Api\TemplateController as TemplateApiController;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ScreenTemplates;
use ProcessMaker\Models\Template;
use ProcessMaker\Templates\ProcessTemplate;

class TemplateController extends Controller
{
    protected array $types = [
        'process' => [Process::class, ProcessTemplate::class],
    ];

    /**
     * @param string $type
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(string $type, Request $request)
    {
        new $this->types[$type][1]->edit($request);
    }

    /**
     * @param string $type
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function import(string $type, Request $request)
    {
        return view('templates.import', compact('type'));
    }

    /**
     * @param string $type
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function configure(string $type, $request)
    {
        [$template, $addons, $categories] = (new $this->types[$type][1])->configure($request);

        return view('templates.configure', compact(['template', 'addons', 'categories']));
    }

    public function show(Request $request)
    {
        $templateApiController = new TemplateApiController(new Template);
        $response = $templateApiController->show('process', $request);
        Session::flash('_alert', json_encode(['success', __('The template was created.')]));

        return view('processes.modeler.showTemplate')->with('id', $response['id']);
    }

    /**
     * Renders the view for choosing template assets.
     */
    public function chooseTemplateAssets()
    {
        return view('templates.assets');
    }

    /**
     * Get screen export page
     *
     * @param ScreenTemplates $screen
     *
     * @return object
     */
    public function export(ScreenTemplates $screen)
    {
        return view('templates.export-screen', compact('screen'));
    }
}
